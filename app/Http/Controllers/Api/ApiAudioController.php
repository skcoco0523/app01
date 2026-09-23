<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\IotDevice;
use App\Models\User;
use App\Models\Mosquitto;
use App\Models\CommonConfig;
use App\Models\VirtualRemoteUser;
use App\Models\IotDeviceSignal;
use App\Models\Ai;
use Exception;

class ApiAudioController extends Controller
{
    /**
     * ESP32から送信された音声データ（WAVバイナリ）を受信し処理する
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_audio_upload(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "==========================================");

        try {

            // ===========================================================================
            // HTTPヘッダー検証
            // ===========================================================================
            $macAddress  = $request->header('X-Mac-Address');
            $serverToken = $request->header('X-Server-Token');

            if (empty($macAddress) || empty($serverToken)) {
                make_error_log($error_log, "Error: Header X-Mac-Address or X-Server-Token is missing.");
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'MISSING_HEADERS',
                    'message' => 'Unauthorized: Missing required headers'
                ], 401);
            }

            // ===========================================================================
            // デバイス（MACアドレス）単位の連続利用制限（1分あたり最大6回まで）
            // ===========================================================================
            $rateKey = "audio_upload_rate_{$macAddress}";

            if (RateLimiter::tooManyAttempts($rateKey, 6)) {
                $seconds = RateLimiter::availableIn($rateKey);
                make_error_log($error_log, "Rate limit exceeded for MAC: {$macAddress}. Wait {$seconds} seconds.");

                return response()->json([
                    'status'  => 'error',
                    'code'    => 'TOO_MANY_REQUESTS',
                    'message' => "Too many requests. Please try again in {$seconds} seconds."
                ], 429);
            }

            RateLimiter::hit($rateKey, 60);

            // ===========================================================================
            // ワンタイムトークン照合
            // ===========================================================================
            $cacheKey    = "audio_upload_token_{$macAddress}";
            $storedToken = Cache::get($cacheKey);

            if (app()->environment('local')) {
                make_error_log($error_log, "Local environment: Skipping token validation for MAC: {$macAddress}");
            } else {
                if (!$storedToken || !hash_equals($storedToken, $serverToken)) {
                    make_error_log($error_log, "Error: Invalid or expired token. MAC: {$macAddress}");
                    return response()->json([
                        'status'  => 'error',
                        'code'    => 'INVALID_TOKEN',
                        'message' => 'Forbidden: Invalid or expired token'
                    ], 403);
                }
            }

            Cache::forget($cacheKey);

            // ===========================================================================
            // IoTデバイス＆ユーザー検証
            // ===========================================================================
            $keyword = [
                'admin_flag'      => true,
                'search_mac_addr' => $macAddress
            ];
            $deviceList = IotDevice::getIotDeviceList(1, false, null, $keyword);
            $device     = (is_array($deviceList) || $deviceList->isEmpty()) ? null : $deviceList->first();

            if (!$device || empty($device->admin_user_id)) {
                make_error_log($error_log, "Error: Device or Admin User not found for MAC: {$macAddress}");
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'DEVICE_OR_USER_NOT_FOUND',
                    'message' => 'Device or User not found'
                ], 404);
            }

            $user = User::find($device->admin_user_id);
            if (!$user) {
                make_error_log($error_log, "Error: User model find failed for ID: {$device->admin_user_id}");
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'USER_NOT_FOUND',
                    'message' => 'User not found'
                ], 404);
            }
            
            // ===========================================================================
            // 設定値取得 必須ポイント額チェック
            // ===========================================================================
            //$common_conf_names = ['po_whisper', 'po_ai_text', 'po_ai_voice'];
            $common_conf_names = ['po_whisper', 'po_ai_voice'];
            $configs           = CommonConfig::getValues($common_conf_names);
            $po_whisper        = $configs['po_whisper']->value1;
            $whisper_free_cnt  = $configs['po_whisper']->value2;
            //$po_ai_text        = $configs['po_ai_text']->value1;
            //$ai_free_cnt       = $configs['po_ai_text']->value2;
            $po_ai_voice       = $configs['po_ai_voice']->value1;
            $ai_voice_free_cnt = $configs['po_ai_voice']->value2;

            $dateKey    = date('Ymd');
            $sttCount   = Cache::get("user_stt_count_{$user->id}_{$dateKey}", 0);
            //$textCount  = Cache::get("user_text_count_{$user->id}_{$dateKey}", 0);
            $voiceCount = Cache::get("user_voice_count_{$user->id}_{$dateKey}", 0);
            $mode       = $device->ai_reply_mode ?? 'command';
            
            if ($mode === 'command') {
                $requiredPoint = ($sttCount < $whisper_free_cnt) ? 0 : $po_whisper;
            } elseif ($mode === 'voice') {
                $requiredPoint = ($voiceCount < $ai_voice_free_cnt) ? 0 : $po_ai_voice;
            } else {
                make_error_log($error_log, "Error: Unknown mode '{$mode}' for device MAC: {$macAddress}");
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'UNKNOWN_MODE',
                    'message' => 'Unknown mode'
                ], 400);
            }

            if ($requiredPoint > 0) {
                $check = $user->po_check($requiredPoint);
                if (!$check['can_use']) {
                    make_error_log($error_log, "Point Depleted: User {$user->id} has insufficient points.");
                    Mosquitto::publishMQTT($macAddress, "point_status", json_encode(["status" => "empty"]));

                    return response()->json([
                        'status'  => 'error',
                        'code'    => 'POINT_DEPLETED',
                        'message' => 'Point depleted',
                        'points'  => $check
                    ], 402);
                }
            }

            // ===========================================================================
            // 音声データの取得 ＆ WAVヘッダーサイズ補正
            // ===========================================================================
            $audioData = $request->getContent();
            if (empty($audioData) || strlen($audioData) < 44) {
                make_error_log($error_log, "Error: No audio data received or data too short.");
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'NO_AUDIO_DATA',
                    'message' => 'No or invalid audio data received'
                ], 400);
            }

            $totalSize   = strlen($audioData);
            $pcmDataSize = $totalSize - 44;

            if ($pcmDataSize < (32000 * 0.5)) {
                make_error_log($error_log, "Error: Audio data too short ({$pcmDataSize} bytes).");
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'AUDIO_TOO_SHORT',
                    'message' => 'Audio recording too short.'
                ], 400);
            }

            $chunkSize = 36 + $pcmDataSize;
            $audioData = substr_replace($audioData, pack('V', $chunkSize), 4, 4);
            $audioData = substr_replace($audioData, pack('V', $pcmDataSize), 40, 4);

            $timestamp = date('Ymd_His');
            $filename  = "audio_{$timestamp}.wav";
            $path      = "audio/{$filename}";

            Storage::put($path, $audioData);

            // ===========================================================================
            // 文字起こし (Ai へ移送)
            // ===========================================================================
            $sttResult = Ai::transcribe($path, $error_log);

            if (!$sttResult['success']) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'STT_FAILED',
                    'message' => 'Speech-to-Text service unavailable'
                ], 502);
            }

            $transcript   = Ai::cleanTranscript($sttResult['transcript']);
            $usedProvider = $sttResult['provider'];

            if (empty($transcript)) {
                make_error_log($error_log, "[STT Warning] Audio contained only noise or silence.");
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'NO_SPEECH_DETECTED',
                    'message' => 'No clear speech detected'
                ], 400);
            }

            // ==========================================================================
            // リモコンリストの生成 (学習型の場合は登録信号も取得)
            // ==========================================================================
            $keyword = [
                'admin_flag'        => true,
                'search_user_id'    => $user->id,
                'search_admin_flag' => true
            ];
            $remote_list          = VirtualRemoteUser::getVirtualRemoteUserList(null, false, null, $keyword); 
            $my_remote            = [];
            $virtual_remote_conf = config('common.virtual_remote');
            
            foreach ($remote_list as $remote) {
                $signals = null;
                if (!$remote->library_flag) {  
                    $signals = IotDeviceSignal::where('remote_id', $remote->remote_id)
                        ->get(['id', 'signal_name'])
                        ->toArray();
                }

                $kind = $virtual_remote_conf[$remote->kind]['name'] ?? 'その他';
                
                $my_remote[] = [
                    'id'           => $remote->remote_id,
                    'name'         => $remote->name,
                    'kind'         => $kind,
                    'device_id'    => $remote->device_id,
                    'device_name'  => $remote->device_name ?? '',
                    'library_flag' => (int)$remote->library_flag,
                    'signals'      => $signals,
                    'protocol'     => $remote->protocol ?? null,
                    'settings'     => $remote->settings,
                ];
            }
            make_error_log($error_log, "my_remote: " . print_r($my_remote, true));

            // ==========================================================================
            // AIによる意図解析 (Ai へ移送)
            // ==========================================================================
            $intentResult = Ai::analyzeIntent($transcript, $my_remote, $error_log);
            make_error_log($error_log, "[AI Intent Analysis]: " . json_encode($intentResult, JSON_UNESCAPED_UNICODE));

            // ==========================================================================
            // モードおよび解析結果に応じた制御
            // ==========================================================================
            if (!empty($intentResult['matched'])) {
                $remoteId = $intentResult['remote_id'] ?? null;
                $action   = $intentResult['action'] ?? null;
                $signalId = $intentResult['signal_id'] ?? null;
                $settings = $intentResult['settings'] ?? null;

                // TODO: 対象機器へ MQTT 経由で赤外線送信命令を発行
                // Mosquitto::publishMQTT($macAddress, "ir_send", json_encode([...]));

                $transcript = $intentResult['message'] ?? '操作を実行しました。';

            } else {
                if ($mode === 'command') {
                    $transcript = $intentResult['message'] ?? '該当するリモコン操作が見つかりませんでした。';
                } elseif ($mode === 'voice') {
                    $transcript = "リモコン操作ではありませんでした: " . $transcript;
                }
            }

            // ===========================================================================
            // 処理完了後にポイント減算 ＆ 利用回数カウントアップ
            // ===========================================================================
            if ($requiredPoint > 0) {
                $poResult = $user->po_use($requiredPoint, false, "音声対話処理({$usedProvider}): {$transcript}");

                if (!$poResult['success']) {
                    make_error_log($error_log, "Error: Point deduction failed.");
                    return response()->json([
                        'status'  => 'error',
                        'code'    => 'POINT_DEDUCTION_FAILED',
                        'message' => $poResult['message']
                    ], 402);
                }

                if ($poResult['total_point'] == 0) {
                    Mosquitto::publishMQTT($macAddress, "point_status", json_encode(["status" => "empty"]));
                }
            } else {
                $poResult = [
                    'free_point'  => $user->free_point,
                    'pay_point'   => $user->pay_point,
                    'total_point' => $user->free_point + $user->pay_point
                ];
            }

            if ($mode === 'command') {
                Cache::put("user_stt_count_{$user->id}_{$dateKey}", $sttCount + 1, now()->endOfDay());
            } elseif ($mode === 'voice') {
                Cache::put("user_voice_count_{$user->id}_{$dateKey}", $voiceCount + 1, now()->endOfDay());
            }

            make_error_log($error_log, "Success: Audio processed. Remaining points: Free={$poResult['free_point']}, Pay={$poResult['pay_point']}");

            return response()->json([
                "status"     => "success",
                "message"    => "Audio received and processed successfully",
                "transcript" => $transcript,
                "filename"   => $filename,
                "points"     => [
                    "free"  => $poResult['free_point'],
                    "pay"   => $poResult['pay_point'],
                    "total" => $poResult['total_point']
                ]
            ], 200);

        } catch (Exception $e) {
            make_error_log($error_log, "Exception: " . $e->getMessage());
            return response()->json([
                "status"  => "error",
                "code"    => "SERVER_ERROR",
                "message" => "Server error: " . $e->getMessage()
            ], 500);
        } finally {
            if (isset($path) && Storage::exists($path)) {
                Storage::delete($path);
            }
        }
    }
}