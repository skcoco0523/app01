<div id="life_theater_slide_add-modal" class="notification-overlay" onclick="closeModal('life_theater_slide_add-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-content" style="max-height: 85vh; overflow: hidden;">
            
            <form action="{{ route('life_theater.slide.store') }}" method="POST">
                @csrf
                <input type="hidden" name="life_theater_id" value="{{ $theater->id ?? $theater->life_theater_id }}">
                <input type="hidden" name="life_theater_media_id" id="add_slide_media_id" value="">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle"></i> コマ（スライド）の追加</h5>
                    <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('life_theater_slide_add-modal')"></button>
                </div>

                {{-- モーダルボディ：高さ制限を超えた場合のみ内部でスクロール --}}
                <div class="modal-body" style="max-height: 62vh; overflow-y: auto;">
                    
                    {{-- タブ切替ヘッダー --}}
                    <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active py-1 small fw-bold add-slide-tab-btn" 
                                id="add-basic-tab-btn" onclick="switchAddSlideTab('basic', this)">
                                <i class="fa-solid fa-file-lines me-1"></i> 基本情報
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link py-1 small fw-bold text-primary add-slide-tab-btn" 
                                id="add-config-tab-btn" onclick="switchAddSlideTab('config', this)">
                                <i class="fa-solid fa-sliders me-1"></i> コマ個別設定
                            </button>
                        </li>
                    </ul>

                    {{-- 【タブ1】基本情報 --}}
                    <div class="add-slide-tab-pane" id="add-tab-pane-basic">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">ラベル（例: 0か月）</label>
                                <input type="text" class="form-control form-control-sm" name="label" placeholder="例: 0か月">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">日付</label>
                                <input type="text" class="form-control form-control-sm" name="slide_date" placeholder="例: 2026.10.05">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">タイトル <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="slide_add_title" name="title" placeholder="コマのタイトル" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">サブタイトル</label>
                            <input type="text" class="form-control form-control-sm" name="subtitle" placeholder="サブタイトル">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">説明文・メッセージ</label>
                            <textarea class="form-control form-control-sm" name="content" rows="3" placeholder="思い出のテキストを入力"></textarea>
                        </div>

                        {{-- メイン画像の選択（ライブラリ連携） --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fa-solid fa-image"></i> メイン画像</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                    onclick="
                                        targetMediaInputId = 'add_slide_media_id';
                                        openModal('life_theater_media-modal', {
                                            media_manage_mode: '0',
                                            media_modal_title_text: '画像を選択'
                                        });
                                    ">
                                    ライブラリから選択
                                </button>
                                <span id="add_slide_media_name" class="small text-muted">未設定</span>
                            </div>

                            {{-- サムネイルプレビュー表示部 --}}
                            <div id="add_slide_media_preview_wrap" class="d-none border rounded p-1 text-center bg-light" style="width: 100px;">
                                <img id="add_slide_media_preview" src="" class="img-fluid rounded" style="height: 70px; object-fit: cover; width: 100%;">
                            </div>
                        </div>
                    </div>

                    {{-- 【タブ2】コマ個別設定 (config_data) --}}
                    <div class="add-slide-tab-pane d-none" id="add-tab-pane-config">
                        <div class="p-2 border rounded bg-light">
                            <small class="text-muted d-block mb-3">このコマだけに適用したい表示・演出設定をカスタマイズできます。</small>

                            @if (!empty($slide_config_definitions))
                                @foreach ($slide_config_definitions as $key => $def)
                                    @php
                                        $isDisabled = ($def['premium'] ?? false) && !$is_premium;
                                        $defaultVal = $def['default'] ?? '';
                                    @endphp
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold mb-1">
                                            @if($def['premium'] ?? false)
                                                <i class="fa-solid fa-crown text-warning me-1"></i>
                                            @endif
                                            {{ $def['label'] }}
                                        </label>
                                        <select class="form-select form-select-sm" name="config_data[{{ $key }}]" {{ $isDisabled ? 'disabled' : '' }}>
                                            @foreach ($def['options'] as $val => $label)
                                                <option value="{{ $val }}" {{ (string)$defaultVal === (string)$val ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (!empty($def['description']))
                                            <small class="text-muted d-block mt-1" style="font-size: 11px;">{{ $def['description'] }}</small>
                                        @endif
                                    </div>
                                @endforeach

                                @if (!$is_premium)
                                    <small class="text-muted d-block mt-2" style="font-size: 11px;">
                                        <i class="fa-solid fa-circle-info me-1"></i> 一部機能はプレミアムパックの購入で利用可能になります。<br>
                                    </small>
                                @endif
                            @else
                                <small class="text-muted">利用可能な設定項目がありません。</small>
                            @endif
                        </div>
                    </div>

                </div>

                <div class="modal-footer row gap-3 justify-content-center m-0 py-2">
                    <button type="button" class="col-5 btn btn-secondary" onclick="closeModal('life_theater_slide_add-modal')">キャンセル</button>
                    <button id="slide_add_confirm_button" type="submit" class="col-5 btn btn-danger disabled">追加</button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
// タブ切り替え関数
function switchAddSlideTab(tabName, btn) {
    document.querySelectorAll('.add-slide-tab-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    document.querySelectorAll('.add-slide-tab-pane').forEach(p => p.classList.add('d-none'));
    const targetPane = document.getElementById('add-tab-pane-' + tabName);
    if (targetPane) targetPane.classList.remove('d-none');
}

document.addEventListener('DOMContentLoaded', function() {
    const slideTitleInput = document.getElementById('slide_add_title');
    const confirmButton = document.getElementById('slide_add_confirm_button');
    const addModal = document.getElementById('life_theater_slide_add-modal');

    function checkInput() {
        if (slideTitleInput && slideTitleInput.value.trim() !== '') 
            confirmButton.classList.remove('disabled');
        else
            confirmButton.classList.add('disabled');
    }

    if (slideTitleInput) {
        slideTitleInput.addEventListener('input', checkInput);
        checkInput();
    }

    if (addModal) {
        addModal.addEventListener('modal:open', function () {
            // モーダルが開く時は必ず「基本情報」タブを初期表示にする
            switchAddSlideTab('basic', document.getElementById('add-basic-tab-btn'));
        });
    }
});
</script>