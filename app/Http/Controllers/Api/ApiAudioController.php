<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache; // ★【追加】Cacheファサード
use Exception;

class ApiAudioController extends Controller
{
    /**
     * ESP32から送信された音声データ（WAVバイナリ）を受信し保存する
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function api_audio_upload(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        try {
            // HTTPヘッダーから MAC アドレスとトークンを取得
            $macAddress  = $request->header('X-Mac-Address');
            $serverToken = $request->header('X-Server-Token');

            if (empty($macAddress) || empty($serverToken)) {
                make_error_log($error_log, "Error: Header X-Mac-Address or X-Server-Token is missing.");
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized: Missing required headers'
                ], 401);
            }

            // Cacheに保存されているトークンと照合
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

            // --- 既存の音声処理 ---
            $audioData = $request->getContent();

            // 44バイト（WAVヘッダーサイズ）未満の場合はエラー処理
            if (empty($audioData) || strlen($audioData) < 44) {
                make_error_log($error_log, "Error: No audio data received or data too short.");
                return response()->json([
                    'status' => 'error',
                    'message' => 'No or invalid audio data received'
                ], 400);
            }

            // WAVヘッダーのサイズ補正処理
            $totalSize = strlen($audioData);
            $pcmDataSize = $totalSize - 44;
            $chunkSize = 36 + $pcmDataSize;

            $audioData = substr_replace($audioData, pack('V', $chunkSize), 4, 4);   // バイト 4-7
            $audioData = substr_replace($audioData, pack('V', $pcmDataSize), 40, 4); // バイト 40-43

            $timestamp = date('Ymd_His');
            $filename = "audio_{$timestamp}.wav";
            $path = "audio/{$filename}";

            Storage::put($path, $audioData);

            make_error_log($error_log, "Success: Audio saved as {$filename}. Size: " . $totalSize . " bytes");

            return response()->json([
                "status" => "success",
                "message" => "Audio received successfully",
                "filename" => $filename
            ], 200);

        } catch (Exception $e) {
            make_error_log($error_log, "Exception: " . $e->getMessage());
            return response()->json([
                "status" => "error",
                "message" => "Server error: " . $e->getMessage()
            ], 500);
        }
    }
}