<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\IotDevice;
use App\Models\User;
use App\Models\Mosquitto;
use App\Models\CommonConfig;
use App\Models\VirtualRemoteUser;
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
                    'status' => 'error',
                    'code'    => 'MISSING_HEADERS',  //コードに応じて顔を変える
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
                    'code'    => 'TOO_MANY_REQUESTS', // ESP32側で「連打防止・少し待ってね」の表情を表示
                    'message' => "Too many requests. Please try again in {$seconds} seconds."
                ], 429); // 429 Too Many Requests
            }

            // カウントを1増やす（有効期限60秒）
            RateLimiter::hit($rateKey, 60);

            // ===========================================================================
            // ワンタイムトークン照合
            // ===========================================================================
            $cacheKey    = "audio_upload_token_{$macAddress}";
            $storedToken = Cache::get($cacheKey);

            
            if (app()->environment('local')) {
                // ローカル環境ではトークン検証をスキップ（開発用）
                make_error_log($error_log, "Local environment: Skipping token validation for MAC: {$macAddress}");
            } else {
                if (!$storedToken || !hash_equals($storedToken, $serverToken)) {
                    make_error_log($error_log, "Error: Invalid or expired token. MAC: {$macAddress}");
                    return response()->json([
                        'status' => 'error',
                        'code'    => 'INVALID_TOKEN',  //コードに応じて顔を変える
                        'message' => 'Forbidden: Invalid or expired token'
                    ], 403);
                }
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
                    'code'    => 'DEVICE_OR_USER_NOT_FOUND',  //コードに応じて顔を変える
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
                    'code'    => 'USER_NOT_FOUND',  //コードに応じて顔を変える
                    'message' => 'User not found'
                ], 404);
            }
            
            // ===========================================================================
            //設定値取得 必須ポイント額チェック
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
            // 当日の利用回数取得 ＆ 必要ポイント数の計算
            // ===========================================================================
            $dateKey    = date('Ymd');
            $sttCount   = Cache::get("user_stt_count_{$user->id}_{$dateKey}", 0);
            $textCount  = Cache::get("user_text_count_{$user->id}_{$dateKey}", 0);
            $voiceCount = Cache::get("user_voice_count_{$user->id}_{$dateKey}", 0);
            $mode = $device->ai_reply_mode ?? 'command';
            
            // モードに応じた固定ポイント・無料枠判定
            if ($mode === 'command') {
                $requiredPoint = ($sttCount < $whisper_free_cnt) ? 0 : $po_whisper;
            } elseif ($mode === 'voice') {
                $requiredPoint = ($voiceCount < $ai_voice_free_cnt) ? 0 : $po_ai_voice;
            } else{
                make_error_log($error_log, "Error: Unknown mode '{$mode}' for device MAC: {$macAddress}");
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'UNKNOWN_MODE',
                    'message' => 'Unknown mode'
                ], 400);
            }

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
                        'code'    => 'POINT_DEPLETED',  //コードに応じて顔を変える
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
                    'code'    => 'NO_AUDIO_DATA',  //コードに応じて顔を変える
                    'message' => 'No or invalid audio data received'
                ], 400);
            }

            $totalSize   = strlen($audioData);
            $pcmDataSize = $totalSize - 44;

            // 0.5秒未満はノイズ・誤押しと判断して即時リジェクト
            if ($pcmDataSize < (32000 * 0.5)) {
                make_error_log($error_log, "Error: Audio data too short ({$pcmDataSize} bytes). Minimum 1 second required.");
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'AUDIO_TOO_SHORT', // ESP32側で「首を振る/短すぎる顔」表示
                    'message' => 'Audio recording too short. Please speak longer than 1 second.'
                ], 400);
            }


            $chunkSize   = 36 + $pcmDataSize;

            $audioData = substr_replace($audioData, pack('V', $chunkSize), 4, 4);   // バイト 4-7
            $audioData = substr_replace($audioData, pack('V', $pcmDataSize), 40, 4); // バイト 40-43

            $timestamp = date('Ymd_His');
            $filename  = "audio_{$timestamp}.wav";
            $path      = "audio/{$filename}";

            Storage::put($path, $audioData);

            
            // ===========================================================================
            // 文字起こし
            // ===========================================================================
            $transcript = null;
            $usedProvider = 'groq';

            // Groq API を呼び出す    Groq失敗時は OpenAI Whisper に切り替え
            try {
                $transcript = $this->transcribeWithGroq($path);
                make_error_log($error_log, "[STT Success] Groq: {$transcript}");
            } catch (Exception $e) {
                make_error_log($error_log, "[STT Fallback] Groq failed.");
                make_error_log($error_log, $e->getMessage());
                $usedProvider = 'whisper';
                try {
                    $transcript = $this->transcribeWithWhisper($path);
                    make_error_log($error_log, "[STT Success] OpenAI Whisper: {$transcript}");

                } catch (Exception $whisperEx) {
                    make_error_log($error_log, "[STT Error] Both Groq and Whisper failed.");
                    make_error_log($error_log, $whisperEx->getMessage());

                    // ポイント処理・カウントアップ前に即時エラー返却（ポイントは消費されない）
                    return response()->json([
                        'status'  => 'error',
                        'code'    => 'STT_FAILED',  //コードに応じて顔を変える
                        'message' => 'Speech-to-Text service unavailable'
                    ], 502); // 502 Bad Gateway
                }
            } finally {
                // エラー・正常完了を問わず、生成したWAVファイルを確実に削除
                if (isset($path) && Storage::exists($path)) {
                    Storage::delete($path);
                }
            }
            // ==========================================================================
            // 文字起こし結果のクレンジング・ハルシネーション判定
            // ==========================================================================
            $transcript = $this->cleanTranscript($transcript);
            if (empty($transcript)) {
                make_error_log($error_log, "[STT Warning] Audio contained only noise or silence.");

                return response()->json([
                    'status'  => 'error',
                    'code'    => 'NO_SPEECH_DETECTED', // ESP32側で「首を傾げる/困り顔」表示
                    'message' => 'No clear speech detected'
                ], 400);
            }


            // ==========================================================================
            // 低原価APIでユーザーの意図を確認する
            // ==========================================================================
            
            $keyword = array(
                'admin_flag'        => true,
                'search_user_id'    => $user->id,
                'search_admin_flag' => true
            );
            $remote_list = VirtualRemoteUser::getVirtualRemoteUserList(null,false,null,$keyword); 
            $my_remote = [];
            $virtual_remote_conf = config('common.virtual_remote');
            
            foreach ($remote_list as $key => $remote) {
                
                $my_remote[] = [
                    'id'           => $remote->id,                             // リモコンID
                    'name'         => $remote->name,                           // リモコン名 (例: エアコン)
                    'kind'         => $virtual_remote_conf[$remote->kind]['name'] ?? 'その他', // 種別名 (例: エアコン)
                    'device_id'    => $remote->device_id,                      // 紐づくIoTデバイスID
                    'device_name'  => $remote->device_name ?? '',              // デバイス名 (例: ナナチ)
                    'library_flag' => (int)$remote->library_flag,              // 1: ライブラリ型(動的制御可), 0: 学習型(RAW/単発)
                    'protocol'     => $remote->protocol ?? null,               // プロトコル名 (例: PANASONIC_AC)
                    'settings'     => $remote->settings,                        // 現在の状態 (配列 or null)
                ];
            }
            make_error_log($error_log, "my_remote:".print_r($my_remote,1));

            //音声から意図を分析
            $intentResult = $this->analyzeIntentWithLLM($transcript, $my_remote, $error_log);

            make_error_log($error_log, "[AI Intent Analysis]: " . json_encode($intentResult, JSON_UNESCAPED_UNICODE));

            // ==========================================================================
            // モードに応じた処理
            // ==========================================================================
            if (!empty($intentResult['matched'])) {
                // 【リモコン操作の意図があった場合】
                $remoteId = $intentResult['remote_id'] ?? null;
                $action   = $intentResult['action'] ?? null;
                $settings = $intentResult['settings'] ?? null;

                // TODO: 対象機器へ MQTT 経由で赤外線送信命令を発行
                // Mosquitto::publishMQTT($macAddress, "ir_send", json_encode([...]));

                // 実行結果メッセージを発信テキストとして更新
                $transcript = $intentResult['message'] ?? '操作を実行しました。';

            } else {
                // 【リモコン操作の意図がなかった場合】
                if ($mode === 'command') {
                    $transcript = '該当するリモコン操作が見つかりませんでした。';
                } elseif ($mode === 'voice') {
                    // 通常の会話応答を生成（※必要に応じて会話用LLMメソッドを呼ぶ）
                    // $transcript = $this->generateVoiceResponseWithLLM($transcript);
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
                        'code'    => 'POINT_DEDUCTION_FAILED',  //コードに応じて顔を変える
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

            // ===========================================================================
            // 処理成功後、利用した機能のカウントのみインクリメント ＆ 不要ファイルの削除
            // ===========================================================================
            if ($mode === 'command') {
                Cache::put("user_stt_count_{$user->id}_{$dateKey}", $sttCount + 1, now()->endOfDay());
            } elseif ($mode === 'voice') {
                Cache::put("user_voice_count_{$user->id}_{$dateKey}", $voiceCount + 1, now()->endOfDay());
            }

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
                "code"    => "SERVER_ERROR",  //コードに応じて顔を変える
                "message" => "Server error: " . $e->getMessage()
            ], 500);
        }finally {
            // エラー・正常完了を問わず、生成したWAVファイルを確実に削除
            if (isset($path) && Storage::exists($path)) {
                Storage::delete($path);
            }
        }
    }
    
    /**
     * Groq API での文字起こし処理
     * 
     * @param string $filePath Storage相対パス (例: audio/audio_20260916_120000.wav)
     * @return string
     * @throws Exception
     */
    private function transcribeWithGroq($filePath)
    {
        $fullPath = Storage::path($filePath);
        if (!file_exists($fullPath)) {
            throw new Exception("音声ファイルが存在しません: {$filePath}");
        }

        $apiKey = config('services.groq.api_key', env('GROQ_API_KEY'));
        if (empty($apiKey)) {
            throw new Exception("Groq API Key が設定されていません。");
        }

        // Groq Audio Transcriptions API 呼び出し
        $response = Http::withToken($apiKey)
            ->timeout(15) // タイムアウト設定(秒)
            ->attach('file', fopen($fullPath, 'r'), basename($fullPath))
            ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                'model'           => 'whisper-large-v3', // または 'whisper-large-v3-turbo'
                'language'        => 'ja',
                'response_format' => 'json',
            ]);

        if ($response->failed()) {
            throw new Exception($response->status());
        }

        $result = $response->json();
        return $result['text'] ?? '';
    }

    /**
     * OpenAI Whisper API での文字起こし処理
     * 
     * @param string $filePath Storage相対パス
     * @return string
     * @throws Exception
     */
    private function transcribeWithWhisper($filePath)
    {
        $fullPath = Storage::path($filePath);
        if (!file_exists($fullPath)) {
            throw new Exception("音声ファイルが存在しません: {$filePath}");
        }

        $apiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        if (empty($apiKey)) {
            throw new Exception("OpenAI API Key が設定されていません。");
        }

        // OpenAI Audio Transcriptions API 呼び出し
        $response = Http::withToken($apiKey)
            ->timeout(30) // OpenAIはGroqより時間がかかるため長めに設定
            ->attach('file', fopen($fullPath, 'r'), basename($fullPath))
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model'           => 'whisper-1',
                'language'        => 'ja',
                'response_format' => 'json',
            ]);

        if ($response->failed()) {
            throw new Exception($response->status());
        }

        $result = $response->json();
        return $result['text'] ?? '';
    }
    /**
     * 文字起こし結果のクレンジング・ハルシネーション判定
     * 
     * @param string $text
     * @return string|null 正常なテキストの場合はそのまま、ノイズ/無音時は null
     */
    private function cleanTranscript($text)
    {
        $text = trim($text);

        // 空文字列、または1文字以下のノイズは除外
        if (mb_strlen($text) <= 1) return null;

        // Whisper特有の定番無音ハルシネーションワードリスト
        $hallucinations = [
            'ご視聴ありがとうございました',
            'チャンネル登録をお願いします',
            'ご視聴いただきありがとうございました',
            '字幕：',
            'Thank you for watching',
        ];

        foreach ($hallucinations as $word) {
            if (mb_strpos($text, $word) !== false) {
                return null;
            }
        }

        return $text;
    }

    /**
     * 低原価LLM (Groq) を使用して、ユーザーの発話からリモコン操作の意図を解析する
     *
     * @param string $transcript
     * @param array $myRemote
     * @param string $error_log
     * @return array
     */
    private function analyzeIntentWithLLM(string $transcript, array $myRemote, string $error_log = ''): array
    {
        $apiKey = config('services.groq.api_key', env('GROQ_API_KEY'));
        if (empty($apiKey)) {
            if ($error_log) make_error_log($error_log, "[AI Intent Error] GROQ_API_KEY が未設定です。");
            return ['matched' => false, 'message' => 'AI APIキーが未設定です。'];
        }

        // リモコンリストのJSON変換
        $remoteJson = json_encode($myRemote, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        // プロンプト文字列の作成
        $systemPrompt = "You are a smart home control AI. "
            . "Analyze the user's spoken input and compare it with the user's remote control list. "
            . "Respond strictly in JSON format.\n\n"
            . "【User Remote List】\n"
            . $remoteJson . "\n\n"
            . "【Rules】\n"
            . "1. If the input matches a remote control in the list (e.g., 'リビングの照明つけて' matches 'リビングの照明'), set 'matched' to true and return the 'remote_id'.\n"
            . "2. 'つけ' or '消して' or '点灯' means power control. Set 'action' to 'power_on' or 'power_off'.\n"
            . "3. If no matching remote control is found, set 'matched' to false.\n\n"
            . "【Output JSON Schema】\n"
            . "{\n"
            . "  \"matched\": true,\n"
            . "  \"remote_id\": 31,\n"
            . "  \"action\": \"power_on\",\n"
            . "  \"settings\": null,\n"
            . "  \"message\": \"リビングの照明をつけました\"\n"
            . "}";

        // Developerプランで現在確実に使えるアクティブモデルリスト（優先順）
        $models = [
            'openai/gpt-oss-20b',   // 最優先: 超爆速(1000 tps)・Developerプラン対応
            'openai/gpt-oss-120b',  // 候補2: 高精度・Developerプラン対応
            'qwen/qwen3.8-27b'      // 候補3: 予備
        ];

        foreach ($models as $model) {
            try {
                $response = Http::withToken($apiKey)
                    ->timeout(10)
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model'           => $model,
                        'messages'        => [
                            ['role' => 'system', 'content' => $systemPrompt],
                            ['role' => 'user', 'content' => $transcript]
                        ],
                        'response_format' => ['type' => 'json_object'],
                        'temperature'     => 0.1,
                    ]);

                if ($response->successful()) {
                    $content = $response->json('choices.0.message.content');
                    $result  = json_decode($content, true);
                    return is_array($result) ? $result : ['matched' => false, 'message' => 'JSON解析エラー'];
                }

                $status  = $response->status();
                $resData = $response->json();
                $errCode = $resData['error']['code'] ?? '';

                // 権限なし(404/model_not_found) または 廃止(400/model_decommissioned) の場合は次のモデルへ
                if ($status === 404 || $status === 400 || $errCode === 'model_decommissioned' || $errCode === 'model_not_found') {
                    if ($error_log) {
                        make_error_log($error_log, "[AI Intent Warning] Model '{$model}' unavailable (Status: {$status}, Code: {$errCode}). Trying next model...");
                    }
                    continue;
                }

                // その他のエラー（認証NGなど）はログに記録して打ち切り
                if ($error_log) {
                    make_error_log($error_log, "[AI Intent Failure] Model: {$model} Status: " . $status . " Response: " . $response->body());
                }
                break;

            } catch (Exception $e) {
                if ($error_log) {
                    make_error_log($error_log, "[AI Intent Exception] Model: {$model} Error: " . $e->getMessage());
                }
            }
        }

        return ['matched' => false, 'message' => 'AI意図解析失敗'];
    }

    // ========================================================================
    // mode別の処理
    // ========================================================================


    

    
    // ========================================================================

}