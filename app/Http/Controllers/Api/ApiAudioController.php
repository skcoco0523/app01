<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\IotDevice;
use App\Models\User;
use App\Models\Mosquitto;
use App\Models\CommonConfig;
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

        try {
            // ===========================================================================
            //設定値取得
            // ===========================================================================
            $common_conf_names = [
                'po_whisper', 'po_ai_text', 'po_ai_voice'
            ];
            $configs = CommonConfig::getValues($common_conf_names);
            $po_whisper = $configs['po_whisper']->value1;
            $whisper_free_cnt = $configs['po_whisper']->value2;
            $po_ai_text = $configs['po_ai_text']->value1;
            $ai_free_cnt = $configs['po_ai_text']->value2;
            $po_ai_voice = $configs['po_ai_voice']->value1;
            $ai_voice_free_cnt = $configs['po_ai_voice']->value2;

            // ===========================================================================
            // HTTPヘッダー検証
            // ===========================================================================
            $macAddress  = $request->header('X-Mac-Address');
            $serverToken = $request->header('X-Server-Token');

            if (empty($macAddress) || empty($serverToken)) {
                make_error_log($error_log, "Error: Header X-Mac-Address or X-Server-Token is missing.");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized: Missing required headers'
                ], 401);
            }

            // ===========================================================================
            // ワンタイムトークン照合
            // ===========================================================================
            $cacheKey    = "audio_upload_token_{$macAddress}";
            $storedToken = Cache::get($cacheKey);

            if (!$storedToken || !hash_equals($storedToken, $serverToken)) {
                make_error_log($error_log, "Error: Invalid or expired token. MAC: {$macAddress}");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Forbidden: Invalid or expired token'
                ], 403);
            }

            // 検証完了後、トークンを即座に削除（ワンタイム化）
            Cache::forget($cacheKey);

            // ===========================================================================
            // IotDevice::getIotDeviceList を使用してデバイスを取得
            // ===========================================================================
            $keyword = [
                'admin_flag'        => true,
                'search_mac_addr'   => $macAddress
            ];
            $deviceList = IotDevice::getIotDeviceList(1, false, null, $keyword);
            $device     = (is_array($deviceList) || $deviceList->isEmpty()) ? null : $deviceList->first();

            if (!$device || empty($device->admin_user_id)) {
                make_error_log($error_log, "Error: Device or Admin User not found for MAC: {$macAddress}");
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Device or User not found'
                ], 404);
            }

            // ===========================================================================
            // ユーザーデータを取得
            // ===========================================================================
            $user = User::find($device->admin_user_id);
            if (!$user) {
                make_error_log($error_log, "Error: User model find failed for ID: {$device->admin_user_id}");
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User not found'
                ], 404);
            }
            // ===========================================================================
            // 当日の利用回数取得 ＆ 必要ポイント数の計算
            // ===========================================================================
            $todayCount = Cache::get("user_stt_count_{$user->id}_" . date('Ymd'), 0);
            $requiredPoint = ($todayCount < $whisper_free_cnt) ? 0 : $po_whisper;

            // ===========================================================================
            // 事前判定（必要ポイントが 1 以上の場合のみ残高チェック）
            // ===========================================================================
            if ($requiredPoint > 0) {
                $check = $user->po_check($requiredPoint);
                if (!$check['can_use']) {
                    make_error_log($error_log, "Point Depleted: User {$user->id} has insufficient points. Free:{$check['free_point']}, Pay:{$check['pay_point']}");

                    // ESP32へ「ポイント切れ（げッそり顔）」通知を送信
                    Mosquitto::publishMQTT($macAddress, "point_status", json_encode(["status" => "empty"]));

                    return response()->json([
                        'status'  => 'error',
                        'message' => 'Point depleted',
                        'points'  => $check
                    ], 402); // 402 Payment Required
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
                    'message' => 'No or invalid audio data received'
                ], 400);
            }

            $totalSize   = strlen($audioData);
            $pcmDataSize = $totalSize - 44;
            $chunkSize   = 36 + $pcmDataSize;

            $audioData = substr_replace($audioData, pack('V', $chunkSize), 4, 4);   // バイト 4-7
            $audioData = substr_replace($audioData, pack('V', $pcmDataSize), 40, 4); // バイト 40-43

            $timestamp = date('Ymd_His');
            $filename  = "audio_{$timestamp}.wav";
            $path      = "audio/{$filename}";

            Storage::put($path, $audioData);

            
            // ===========================================================================
            // 文字起こし ＆ AI解析
            // ===========================================================================
            $transcript = null;
            $usedProvider = 'groq';

            try {
                // Groq API を呼び出す
                $transcript = $this->transcribeWithGroq($path);
                make_error_log($error_log, "[STT Success] Groq: {$transcript}");

            } catch (Exception $e) {
                // Groq失敗時はログを残して OpenAI Whisper に切り替え
                make_error_log($error_log, "[STT Fallback] Groq failed ({$e->getMessage()}). Switching to OpenAI Whisper.");
                $usedProvider = 'whisper';
                
                // OpenAI Whisper API を呼び出す
                $transcript = $this->transcribeWithWhisper($path);
                make_error_log($error_log, "[STT Success] OpenAI Whisper: {$transcript}");
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
                        'message' => $poResult['message']
                    ], 402);
                }

                if ($poResult['total_point'] == 0) {
                    Mosquitto::publishMQTT($macAddress, "point_status", json_encode(["status" => "empty"]));
                }
            } else {
                // 無料枠消費時のレスポンス用ダミー結果
                $poResult = [
                    'free_point'  => $user->free_point,
                    'pay_point'   => $user->pay_point,
                    'total_point' => $user->free_point + $user->pay_point
                ];
            }

            // 当日の利用回数をインクリメント（24時間で自動消去）
            Cache::put("user_stt_count_{$user->id}_" . date('Ymd'), $todayCount + 1, now()->endOfDay());
            
            // ===========================================================================
            // 処理完了レスポンスを返却
            // ===========================================================================
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
                "message" => "Server error: " . $e->getMessage()
            ], 500);
        }
    }
    /**
     * Groq API での文字起こし処理（プライベートメソッド）
     */
    private function transcribeWithGroq($filePath)
    {
        // TODO: Groq API の呼び出し実装（Curl/Guzzle等）
        // 現時点ではスタブ
        return "テスト (Groq)";
    }

    /**
     * OpenAI Whisper API での文字起こし処理（プライベートメソッド）
     */
    private function transcribeWithWhisper($filePath)
    {
        // TODO: OpenAI API の呼び出し実装（Curl/Guzzle等）
        // 現時点ではスタブ
        return "テスト (Whisper)";
    }

}