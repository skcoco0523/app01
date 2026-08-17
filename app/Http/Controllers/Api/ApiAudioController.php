<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        // 規約に基づいたログファイル名の定義
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        try {
            // 音声バイナリデータの取得
            // Content-Type: audio/wav 等でボディに直接バイナリが送られることを想定
            $audioData = $request->getContent();

            if (empty($audioData)) {
                make_error_log($error_log, "Error: No audio data received.");
                return response()->json([
                    'status' => 'error',
                    'message' => 'No audio data received'
                ], 400);
            }

            // ファイル名の生成 (audio_YYYYMMDD_HHMMSS.wav)
            $timestamp = date('Ymd_His');
            $filename = "audio_{$timestamp}.wav";
            $path = "audio/{$filename}";

            // ストレージに保存 (デフォルトは storage/app/audio/)
            // Storage::put はディレクトリが存在しない場合、自動的に作成する
            Storage::put($path, $audioData);

            make_error_log($error_log, "Success: Audio saved as {$filename}. Size: " . strlen($audioData) . " bytes");

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
