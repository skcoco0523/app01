<div class="card border-0 shadow-sm">
    <!-- ヘッダー & ユーザーポイント情報 -->
    <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h6 class="mb-0 fw-bold text-dark">AI動作テスト（パイプライン検証）</h6>
        
        <!-- 所有ポイント & 消費予定ポイント表示 -->
        <div class="d-flex align-items-center gap-3 small">
            <div class="text-muted">
                ユーザー: <strong>{{ $user->name ?? '' }}</strong>
            </div>
            <div class="badge bg-light text-dark border">
                無償: <strong class="text-primary">{{ number_format($profile->free_point ?? 0) }}</strong> pt
            </div>
            <div class="badge bg-light text-dark border">
                有償: <strong class="text-success">{{ number_format($profile->pay_point ?? 0) }}</strong> pt
            </div>
            @if(isset($cost_summary))
                <div class="badge bg-warning text-dark border">
                    今回消費予定: <strong>{{ number_format($cost_summary['required_pt'] ?? 0) }}</strong> pt
                </div>
            @endif
        </div>
    </div>

    <div class="card-body p-3">
        @if(isset($msg))
            <div class="alert alert-info alert-dismissible fade show py-2 mb-3 small" role="alert">
                {{ $msg }}
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <form action="{{ route('admin.system.ai_test.exec') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="row g-2">
                <div class="col-md-6">
                    <!-- 入力ソース & テキスト/ファイル入力行 -->
                    <div class="row g-2 align-items-center mb-2 p-2 bg-light rounded border m-0">
                        <div class="col-md-auto pe-3 border-end">
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="input_type" id="input_text" value="text" {{ ($input_type ?? 'text') === 'text' ? 'checked' : '' }} onchange="toggleInputSource()">
                                <label class="form-check-label small fw-bold" for="input_text">テキスト入力</label>
                            </div>
                            <div class="form-check form-check-inline mb-0">
                                <input class="form-check-input" type="radio" name="input_type" id="input_audio" value="audio" {{ ($input_type ?? '') === 'audio' ? 'checked' : '' }} onchange="toggleInputSource()">
                                <label class="form-check-label small fw-bold" for="input_audio">WAV音声</label>
                            </div>
                        </div>

                        <div class="col-md" id="area_text_input">
                            <input type="text" name="transcript" value="{{ $transcript ?? '' }}" class="form-control form-control-sm" placeholder="発話テキストを入力 (例: リビングの照明つけて)">
                        </div>

                        <div class="col-md" id="area_audio_input" style="display: none;">
                            <input type="file" name="audio_file" accept=".wav" class="form-control form-control-sm">
                        </div>

                        <div class="col-md-auto ms-auto">
                            <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">実行</button>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <!-- パイプライン制御トグルスイッチ -->
                    <div class="d-flex flex-wrap align-items-center gap-3 p-2 bg-light rounded border mb-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="flag_stt" name="flag_stt" value="1" {{ !empty($flags['flag_stt']) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="flag_stt">① STT (文字起こし)</label>
                        </div>

                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="flag_intent" name="flag_intent" value="1" {{ !empty($flags['flag_intent']) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="flag_intent">② LLM (意図解析)</label>
                        </div>

                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="flag_response" name="flag_response" value="1" {{ !empty($flags['flag_response']) ? 'checked' : '' }}>
                            <label class="form-check-label small text-primary fw-bold" for="flag_response">③ APIレスポンス生成</label>
                        </div>

                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="flag_chat" name="flag_chat" value="1" {{ !empty($flags['flag_chat']) ? 'checked' : '' }}>
                            <label class="form-check-label small" for="flag_chat">④ AI 会話応答</label>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- 実行結果エリア（コスト・原価サマリーつき） -->
        @if(isset($stt_result) || isset($intent_res) || isset($api_response) || isset($cost_summary))
            <hr class="my-3">

            <!-- API試算原価サマリーバー -->
            @if(!empty($cost_summary['total_jpy']))
                <div class="alert alert-dark p-2 mb-3 d-flex justify-content-between align-items-center extra-small">
                    <div>
                        <strong>【API試算原価サマリー】</strong> 
                        STT(文字起こし)原価: <strong>{{ $cost_summary['stt_jpy'] }}円</strong> | 
                        LLM(意図解析)原価: <strong>{{ $cost_summary['llm_jpy'] }}円</strong>
                    </div>
                    <div>
                        合計実コスト: <strong class="fs-6 text-success">{{ $cost_summary['total_jpy'] }} 円</strong>
                    </div>
                </div>
            @endif

            <div class="row g-2">
                <!-- ① STT結果 -->
                @if(isset($stt_result))
                    <div class="col-md-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="small">① STT 文字起こし</strong>
                                <span class="badge {{ $stt_result['status'] === 'success' ? 'bg-success' : 'bg-secondary' }}">{{ $stt_result['status'] }}</span>
                            </div>
                            <pre class="bg-dark text-white p-2 rounded mb-0 extra-small" style="max-height: 150px; overflow-y: auto;"><code>{{ json_encode($stt_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    </div>
                @endif

                <!-- ② LLM意図解析結果 -->
                @if(isset($intent_res))
                    <div class="col-md-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="small">② LLM 意図解析 (Groq)</strong>
                                <span class="badge {{ !empty($intent_res['matched']) ? 'bg-success' : 'bg-secondary' }}">matched: {{ !empty($intent_res['matched']) ? 'true' : 'false' }}</span>
                            </div>
                            <pre class="bg-dark text-white p-2 rounded mb-0 extra-small" style="max-height: 150px; overflow-y: auto;"><code>{{ json_encode($intent_res, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    </div>
                @endif

                <!-- ③ APIレスポンス結果 -->
                @if(isset($api_response))
                    <div class="col-md-6">
                        <div class="border rounded p-2 bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <strong class="small">③ ESP32返却用 APIレスポンス</strong>
                                <span class="badge {{ ($api_response['status'] ?? '') === 'success' ? 'bg-primary' : 'bg-secondary' }}">{{ $api_response['status'] ?? 'skipped' }}</span>
                            </div>
                            <pre class="bg-dark text-white p-2 rounded mb-0 extra-small" style="max-height: 150px; overflow-y: auto;"><code>{{ json_encode($api_response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    </div>
                @endif

                <!-- リモコンリスト情報 -->
                @if(!empty($my_remote))
                    <div class="col-md-12">
                        <details class="border rounded p-2 bg-light">
                            <summary class="small fw-bold text-muted cursor-pointer">参照用リモコンデータ (my_remote) を表示/非表示</summary>
                            <pre class="bg-dark text-white p-2 rounded mb-0 mt-2 extra-small" style="max-height: 150px; overflow-y: auto;"><code>{{ json_encode($my_remote, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</code></pre>
                        </details>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<style>
.extra-small {
    font-size: 0.75rem;
}
.cursor-pointer {
    cursor: pointer;
}
</style>

<script>
function toggleInputSource() {
    const isAudio = document.getElementById('input_audio').checked;
    document.getElementById('area_text_input').style.display = isAudio ? 'none' : 'block';
    document.getElementById('area_audio_input').style.display = isAudio ? 'block' : 'none';
}
document.addEventListener('DOMContentLoaded', toggleInputSource);
</script>