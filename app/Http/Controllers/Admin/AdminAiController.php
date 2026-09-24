<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\CommonConfig;
use App\Models\VirtualRemoteUser;
use App\Models\IotDeviceSignal;
use App\Models\Ai;
use App\Models\Ir;
use Exception;

class AdminAiController extends Controller
{
    /**
     * AI動作テスト画面の表示
     */
    public function ai_test(Request $request)
    {
        $user        = auth()->user();
        $profile     = User::getProfile($user->id);
        $config_type = 'ai_test';

        $flags = [
            'flag_stt'      => 1,
            'flag_intent'   => 1,
            'flag_response' => 1,
            'flag_chat'     => 1, // 会話ONでも操作優先で動作
        ];

        return view('admin.admin_home', compact('user', 'profile', 'config_type', 'flags'));
    }

    /**
     * AI動作テストのパイプライン実行処理
     */
    public function ai_test_exec(Request $request)
    {
        $user        = auth()->user();
        $profile     = User::getProfile($user->id);
        $config_type = 'ai_test';
        $error_log   = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        $input_type = $request->input('input_type', 'text');
        $flags = [
            'flag_stt'      => (int)$request->has('flag_stt'),
            'flag_intent'   => (int)$request->has('flag_intent'),
            'flag_response' => (int)$request->has('flag_response'),
            'flag_chat'     => (int)$request->has('flag_chat'),
        ];

        $transcript   = $request->input('transcript');
        $tempPath     = null;
        $my_remote    = [];
        $stt_result   = null;
        $intent_res   = null;
        $api_response = null;
        $chat_res     = null;
        $cost_summary = ['stt_jpy' => 0, 'llm_jpy' => 0, 'total_jpy' => 0, 'required_pt' => 0, 'pt_mode' => ''];

        // 設定値の取得
        $common_conf_names = ['po_whisper', 'po_ai_voice'];
        $configs           = CommonConfig::getValues($common_conf_names);
        $po_whisper        = $configs['po_whisper']->value1 ?? 0;
        $whisper_free_cnt  = $configs['po_whisper']->value2 ?? 0;
        $po_ai_voice       = $configs['po_ai_voice']->value1 ?? 0;
        $ai_voice_free_cnt = $configs['po_ai_voice']->value2 ?? 0;

        $dateKey    = date('Ymd');
        $sttCount   = Cache::get("user_stt_count_{$user->id}_{$dateKey}", 0);
        $voiceCount = Cache::get("user_voice_count_{$user->id}_{$dateKey}", 0);

        try {
            // ===================================================================
            // フェーズ 1: STT (文字起こし)
            // ===================================================================
            if ($input_type === 'audio') {
                if ($flags['flag_stt']) {
                    if (!$request->hasFile('audio_file') || !$request->file('audio_file')->isValid()) {
                        $msg = 'WAV音声ファイルをアップロードしてください。';
                        return view('admin.admin_home', compact('user', 'profile', 'config_type', 'input_type', 'flags', 'cost_summary', 'msg'));
                    }

                    $file     = $request->file('audio_file');
                    $filename = "test_audio_" . date('Ymd_His') . ".wav";
                    $tempPath = $file->storeAs('audio', $filename);

                    $sttResData = Ai::transcribe($tempPath, $error_log);
                    if (!$sttResData['success']) {
                        $msg = '文字起こし処理に失敗しました。';
                        return view('admin.admin_home', compact('user', 'profile', 'config_type', 'input_type', 'flags', 'cost_summary', 'msg'));
                    }

                    $transcript = Ai::cleanTranscript($sttResData['transcript']);
                    
                    $durationSec = $sttResData['duration'] ?? 0;
                    $sttCostUsd  = $durationSec * (0.111 / 3600);
                    $sttCostJpy  = round($sttCostUsd * 150, 4);

                    $stt_result = [
                        'status'   => 'success',
                        'raw_text' => $sttResData['transcript'],
                        'cleaned'  => $transcript,
                        'provider' => $sttResData['provider'],
                        'duration' => "{$durationSec} 秒",
                        'cost_jpy' => "約 {$sttCostJpy} 円",
                    ];

                    $cost_summary['stt_jpy'] = $sttCostJpy;
                }
            }

            if (empty($transcript)) {
                $msg = '発話テキストが存在しないため処理を中断しました。';
                return view('admin.admin_home', compact('user', 'profile', 'config_type', 'input_type', 'flags', 'transcript', 'stt_result', 'cost_summary', 'msg'));
            }

            // ===================================================================
            // フェーズ 2: LLM 意図解析 (リモコン照合)
            // ===================================================================
            if ($flags['flag_intent']) {
                $keyword = ['admin_flag' => true, 'search_user_id' => $user->id, 'search_admin_flag' => true];
                $remote_list          = VirtualRemoteUser::getVirtualRemoteUserList(null, false, null, $keyword);
                $virtual_remote_conf = config('common.virtual_remote');

                foreach ($remote_list as $remote) {
                    $signals = null;
                    if (!$remote->library_flag) {
                        $signals = IotDeviceSignal::where('remote_id', $remote->remote_id)->get(['id', 'signal_name'])->toArray();
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

                $intent_res = Ai::analyzeIntent($transcript, $my_remote, $error_log);

                if (!empty($intent_res['usage'])) {
                    $pTokens = $intent_res['usage']['prompt_tokens'] ?? 0;
                    $cTokens = $intent_res['usage']['completion_tokens'] ?? 0;
                    
                    $llmCostUsd = ($pTokens * (0.075 / 1000000)) + ($cTokens * (0.60 / 1000000));
                    $llmCostJpy = round($llmCostUsd * 150, 4);

                    $intent_res['cost_info'] = [
                        'model'    => $intent_res['usage']['model'] ?? '',
                        'tokens'   => "Input: {$pTokens} / Output: {$cTokens}",
                        'cost_jpy' => "約 {$llmCostJpy} 円",
                    ];

                    $cost_summary['llm_jpy'] = $llmCostJpy;
                }
            }

            // ===================================================================
            // フェーズ 3 & 4: 意図判定結果に基づく分岐とポイント試算
            // ===================================================================
            $isMatched = !empty($intent_res['matched']);

            if ($isMatched) {
                // ---------------------------------------------------------------
                // 【A. リモコン操作の意図があった場合】
                // ---------------------------------------------------------------
                // 1. 会話機能は不要なため自動スキップ（会話用LLMは呼ばない）
                if ($flags['flag_chat']) {
                    $chat_res = [
                        'status'  => 'skipped',
                        'message' => 'リモコン操作(matched: true)のため、会話AIは呼び出さず自動スキップされました。',
                    ];
                }

                // 2. ESP32用レスポンス生成
                if ($flags['flag_response']) {
                    $irParams = [
                        'user_id'   => $user->id,
                        'remote_id' => $intent_res['remote_id'] ?? null,
                        'signal_id' => $intent_res['signal_id'] ?? null,
                        'action'    => $intent_res['action'] ?? null,
                    ];

                    if (!empty($intent_res['settings']) && is_array($intent_res['settings'])) {
                        $irParams = array_merge($irParams, $intent_res['settings']);
                    }

                    // ★ 実際に赤外線送信（MQTTパブリッシュ）を実行
                    $irResult = Ir::sendSignal($irParams);

                    $api_response = [
                        'status'     => $irResult['success'] ? 'success' : 'error',
                        'code'       => 'REMOTE_ACTION',
                        'message'    => $irResult['msg'] ?? ($intent_res['message'] ?? '操作を実行します。'),
                        'transcript' => $transcript,
                        'command'    => [
                            'remote_id' => $intent_res['remote_id'] ?? null,
                            'signal_id' => $intent_res['signal_id'] ?? null,
                            'action'    => $intent_res['action'] ?? null,
                            'settings'  => $intent_res['settings'] ?? null,
                        ],
                        'mqtt_result' => $irResult
                    ];
                }

                // 3. ポイント判定: コマンド操作用ポイント(po_whisper)を適用
                $cost_summary['required_pt'] = ($sttCount < $whisper_free_cnt) ? 0 : $po_whisper;
                $cost_summary['pt_mode']     = "コマンド操作枠 (po_whisper: {$po_whisper}pt)";

            } else {
                // ---------------------------------------------------------------
                // 【B. リモコン操作ではないと判定された場合】
                // ---------------------------------------------------------------
                if ($flags['flag_chat']) {
                    // 会話生成AIを実行
                    $chat_res = [
                        'status'   => 'generated',
                        'response' => "「{$transcript}」についてですね。リモコン操作以外のご案内文をAIが生成しました。",
                    ];

                    // ポイント判定: 会話応答用ポイント(po_ai_voice)を適用 (例: 5pt)
                    $cost_summary['required_pt'] = ($voiceCount < $ai_voice_free_cnt) ? 0 : $po_ai_voice;
                    $cost_summary['pt_mode']     = "会話応答枠 (po_ai_voice: {$po_ai_voice}pt)";
                } else {
                    $chat_res = [
                        'status'  => 'skipped',
                        'message' => 'リモコン操作対象外で、会話フラグもOFFのため定型メッセージを返却します。',
                    ];

                    // ポイント判定: コマンド枠扱い
                    $cost_summary['required_pt'] = ($sttCount < $whisper_free_cnt) ? 0 : $po_whisper;
                    $cost_summary['pt_mode']     = "コマンド操作枠 (po_whisper: {$po_whisper}pt)";
                }
            }

            $cost_summary['total_jpy'] = round($cost_summary['stt_jpy'] + $cost_summary['llm_jpy'], 4);
            $msg = 'パイプラインテスト処理が完了しました。';

        } catch (Exception $e) {
            $msg = 'エラーが発生しました: ' . $e->getMessage();
        } finally {
            if ($tempPath && Storage::exists($tempPath)) {
                Storage::delete($tempPath);
            }
        }

        return view('admin.admin_home', compact(
            'user', 'profile', 'config_type', 'input_type', 'flags', 'transcript',
            'my_remote', 'stt_result', 'intent_res', 'api_response', 'chat_res', 'cost_summary', 'msg'
        ));
    }
}