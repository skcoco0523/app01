<?php

namespace App\Models;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Exception;

class Ai
{
    /**
     * 音声ファイルを文字起こしする (Groq -> Whisper フォールバック)
     */
    public static function transcribe(string $file_path, string $error_log = ''): array
    {
        $fullPath = Storage::path($file_path);
        if (!file_exists($fullPath)) {
            if ($error_log) make_error_log($error_log, "[STT Error] 音声ファイルが存在しません: {$file_path}");
            return ['success' => false, 'transcript' => '', 'provider' => '', 'duration' => 0, 'message' => 'ファイル未存在'];
        }

        // 音声ファイルの秒数を概算取得 (16kHz 16bit Mono PCM = 32,000 bytes/sec)
        $fileSize = filesize($fullPath);
        $pcmSize  = max(0, $fileSize - 44);
        $duration = round($pcmSize / 32000, 2);

        $groqApiKey = config('services.groq.api_key', env('GROQ_API_KEY'));
        if (!empty($groqApiKey)) {
            try {
                $response = Http::withToken($groqApiKey)
                    ->timeout(15)
                    ->attach('file', fopen($fullPath, 'r'), basename($fullPath))
                    ->post('https://api.groq.com/openai/v1/audio/transcriptions', [
                        'model'           => 'whisper-large-v3',
                        'language'        => 'ja',
                        'response_format' => 'json',
                    ]);

                if ($response->successful()) {
                    $text = $response->json('text') ?? '';
                    return [
                        'success'    => true,
                        'transcript' => $text,
                        'provider'   => 'groq',
                        'duration'   => $duration,
                        'message'    => ''
                    ];
                }
            } catch (Exception $e) {
                if ($error_log) make_error_log($error_log, "[STT Fallback] Groq Exception: " . $e->getMessage());
            }
        }

        // OpenAI Whisper へのフォールバック
        $openaiApiKey = config('services.openai.api_key', env('OPENAI_API_KEY'));
        if (!empty($openaiApiKey)) {
            try {
                $response = Http::withToken($openaiApiKey)
                    ->timeout(30)
                    ->attach('file', fopen($fullPath, 'r'), basename($fullPath))
                    ->post('https://api.openai.com/v1/audio/transcriptions', [
                        'model'           => 'whisper-1',
                        'language'        => 'ja',
                        'response_format' => 'json',
                    ]);

                if ($response->successful()) {
                    $text = $response->json('text') ?? '';
                    return [
                        'success'    => true,
                        'transcript' => $text,
                        'provider'   => 'whisper',
                        'duration'   => $duration,
                        'message'    => ''
                    ];
                }
            } catch (Exception $e) {
                if ($error_log) make_error_log($error_log, "[STT Error] OpenAI Exception: " . $e->getMessage());
            }
        }

        return ['success' => false, 'transcript' => '', 'provider' => '', 'duration' => 0, 'message' => '全STTサービス利用不可'];
    }

    /**
     * 発話テキストとリモコン一覧から操作意図を解析する (Groq)
     */
    public static function analyzeIntent(string $transcript, array $my_remote, string $error_log = ''): array
    {
        $apiKey = config('services.groq.api_key', env('GROQ_API_KEY'));
        if (empty($apiKey)) {
            return ['matched' => false, 'message' => 'AI APIキーが未設定です。'];
        }

        $remoteJson = json_encode($my_remote, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $nowData    = date('Y-m-d H:i:s (D)');

        // ★ プロンプトに学習型(library_flag=0)とライブラリ型(library_flag=1)の明確な指示を追加
        $systemPrompt = "Current Date & Time: {$nowData}\n\n"
            . "You are a smart home control AI.\n"
            . "Analyze the user's spoken input and compare it with the user's remote control list.\n"
            . "Respond strictly in JSON format.\n\n"
            . "【User Remote List】\n"
            . $remoteJson . "\n\n"
            . "【Rules】\n"
            . "1. If the input matches a remote control, set 'matched' to true and return 'remote_id'.\n"
            . "2. For 'library_flag' = 0 (Learned / RAW Remote):\n"
            . "   - You MUST match the user request with one of the items in the 'signals' array.\n"
            . "   - Return the matched signal's 'id' as 'signal_id'.\n"
            . "   - Set 'action' to null and 'settings' to null.\n"
            . "3. For 'library_flag' = 1 (Library / Smart Remote):\n"
            . "   - Set 'signal_id' to null.\n"
            . "   - Set 'action' (e.g., 'power_on', 'power_off') or 'settings' (e.g., temp, mode, fan, power).\n"
            . "4. If no matching remote or signal is found, set 'matched' to false.\n\n"
            . "【Output JSON Format】\n"
            . "{\n"
            . "  \"matched\": boolean,\n"
            . "  \"remote_id\": number_or_null,\n"
            . "  \"signal_id\": number_or_null,\n"
            . "  \"action\": string_or_null,\n"
            . "  \"settings\": object_or_null,\n"
            . "  \"message\": string\n"
            . "}";

        $models = ['openai/gpt-oss-20b', 'openai/gpt-oss-120b'];

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
                    $usage   = $response->json('usage') ?? [];
                    $result  = json_decode($content, true);

                    if (is_array($result)) {
                        // ★ 安全対策: 学習型(library_flag=0)で signal_id が未設定の場合の自動補完ロジック
                        if (!empty($result['matched']) && !empty($result['remote_id']) && empty($result['signal_id'])) {
                            foreach ($my_remote as $remote) {
                                if ($remote['id'] == $result['remote_id'] && (int)$remote['library_flag'] === 0 && !empty($remote['signals'])) {
                                    $targetKeyword = $result['action'] ?? $transcript;
                                    foreach ($remote['signals'] as $sig) {
                                        if (mb_strpos($sig['signal_name'], $targetKeyword) !== false || mb_strpos($transcript, $sig['signal_name']) !== false) {
                                            $result['signal_id'] = $sig['id'];
                                            $result['action']    = null;
                                            break;
                                        }
                                    }
                                    if (empty($result['signal_id']) && !empty($remote['signals'][0]['id'])) {
                                        $result['signal_id'] = $remote['signals'][0]['id'];
                                        $result['action']    = null;
                                    }
                                }
                            }
                        }

                        $result['usage'] = [
                            'prompt_tokens'     => $usage['prompt_tokens'] ?? 0,
                            'completion_tokens' => $usage['completion_tokens'] ?? 0,
                            'total_tokens'      => $usage['total_tokens'] ?? 0,
                            'model'             => $model,
                        ];
                        return $result;
                    }
                }
            } catch (Exception $e) {
                if ($error_log) make_error_log($error_log, "[AI Intent Exception] " . $e->getMessage());
            }
        }

        return ['matched' => false, 'message' => 'AI意図解析失敗'];
    }

    /**
     * 文字起こし結果のクレンジング
     */
    public static function cleanTranscript(?string $text): ?string
    {
        if (empty($text)) return null;
        $text = trim($text);
        if (mb_strlen($text) <= 1) return null;

        $hallucinations = [
            'ご視聴ありがとうございました',
            'チャンネル登録をお願いします',
            '字幕：',
            'Thank you for watching',
        ];
        foreach ($hallucinations as $word) {
            if (mb_strpos($text, $word) !== false) return null;
        }

        return $text;
    }
}