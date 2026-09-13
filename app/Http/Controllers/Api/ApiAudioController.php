<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\IotDevice;
use App\Models\User;
use App\Models\Mosquitto;
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
            // 1. HTTPヘッダー検証
            $macAddress  = $request->header('X-Mac-Address');
            $serverToken = $request->header('X-Server-Token');

            if (empty($macAddress) || empty($serverToken)) {
                make_error_log($error_log, "Error: Header X-Mac-Address or X-Server-Token is missing.");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized: Missing required headers'
                ], 401);
            }

            // 2. ワンタイムトークン照合
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

            // 3. IotDevice::getIotDeviceList を使用してデバイスを取得
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

            // 4. User モデルを通して実データを取得
            $user = User::find($device->admin_user_id);
            if (!$user) {
                make_error_log($error_log, "Error: User model find failed for ID: {$device->admin_user_id}");
                return response()->json([
                    'status'  => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            // 5. 【事前判定】Userモデルの po_check() で実DBのポイント残高を確認
            $check = $user->po_check(1);
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

            // 6. 音声データの取得 ＆ WAVヘッダーサイズ補正
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

            // ===============================================
            // 7. 【仮処理】Whisper API 文字起こし ＆ AI解析
            // ===============================================
            $transcript = "テスト"; 
            make_error_log($error_log, "[STT Stub] Transcript: {$transcript}");

            // ===============================================
            // 8. 【成功確定】処理完了後に po_use() でポイント減算
            // ===============================================
            $poResult = $user->po_use(1, false, "音声対話処理: {$transcript}");

            if (!$poResult['success']) {
                make_error_log($error_log, "Error: Point deduction failed.");
                return response()->json([
                    'status'  => 'error',
                    'message' => $poResult['message']
                ], 402);
            }

            // ポイント消費の結果、合計残高が 0pt になった場合はESP32を即座に「げッそり顔」へ変更
            if ($poResult['total_point'] == 0) {
                Mosquitto::publishMQTT($macAddress, "point_status", json_encode(["status" => "empty"]));
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
                "message" => "Server error: " . $e->getMessage()
            ], 500);
        }
    }
}