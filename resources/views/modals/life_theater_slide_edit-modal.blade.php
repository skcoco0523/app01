<div id="life_theater_slide_edit-modal" class="notification-overlay" onclick="closeModal('life_theater_slide_edit-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-content" style="max-height: 85vh; overflow: hidden;">
            
            <form action="{{ route('life_theater.slide.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_slide_id" value="">
                <input type="hidden" name="life_theater_media_id" id="edit_slide_media_id" value="">
                <input type="hidden" id="edit_slide_media_url" value="">
                {{-- ★ openModalで値を受け取るための隠しフィールドを追加 --}}
                <input type="hidden" id="edit_slide_config_data" value="">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square"></i> コマ（スライド）の編集</h5>
                    <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('life_theater_slide_edit-modal')"></button>
                </div>

                {{-- モーダルボディ --}}
                <div class="modal-body" style="max-height: 62vh; overflow-y: auto;">
                    
                    {{-- タブ切替ヘッダー --}}
                    <ul class="nav nav-tabs nav-justified mb-3" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active py-1 small fw-bold edit-slide-tab-btn" 
                                id="edit-basic-tab-btn" onclick="switchEditSlideTab('basic', this)">
                                <i class="fa-solid fa-file-lines me-1"></i> 基本情報
                            </button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link py-1 small fw-bold text-primary edit-slide-tab-btn" 
                                id="edit-config-tab-btn" onclick="switchEditSlideTab('config', this)">
                                <i class="fa-solid fa-sliders me-1"></i> コマ個別設定
                            </button>
                        </li>
                    </ul>

                    {{-- 【タブ1】基本情報 --}}
                    <div class="edit-slide-tab-pane" id="edit-tab-pane-basic">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">ラベル</label>
                                <input type="text" class="form-control form-control-sm" id="edit_slide_label" name="label">
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">日付</label>
                                <input type="text" class="form-control form-control-sm" id="edit_slide_date" name="slide_date">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">タイトル <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="edit_slide_title" name="title" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">サブタイトル</label>
                            <input type="text" class="form-control form-control-sm" id="edit_slide_subtitle" name="subtitle">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">説明文・メッセージ</label>
                            <textarea class="form-control form-control-sm" id="edit_slide_content" name="content" rows="3"></textarea>
                        </div>

                        {{-- メイン画像の選択 --}}
                        <div class="mb-3">
                            <label class="form-label fw-bold"><i class="fa-solid fa-image"></i> メイン画像</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" 
                                    onclick="
                                        targetMediaInputId = 'edit_slide_media_id';
                                        openModal('life_theater_media-modal', {
                                            media_manage_mode: '0',
                                            media_modal_title_text: '画像を選択'
                                        });
                                    ">
                                    ライブラリから変更
                                </button>
                                <span id="edit_slide_media_name" class="small text-muted">未設定</span>
                            </div>

                            <div id="edit_slide_media_preview_wrap" class="d-none border rounded p-1 text-center bg-light" style="width: 100px;">
                                <img id="edit_slide_media_preview" src="" class="img-fluid rounded" style="height: 70px; object-fit: cover; width: 100%;">
                            </div>
                        </div>

                        {{-- キャスト編集ボタン --}}
                        <div class="border-top pt-2 mt-3">
                            <div class="d-flex align-items-center justify-content-between bg-light p-2 border rounded">
                                <small class="text-muted">人物や吹き出しの編集</small>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="
                                        const slideId = document.getElementById('edit_slide_id').value;
                                        openModal('life_theater_slide_object-modal', {
                                            object_target_slide_id: slideId
                                        });
                                    ">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> キャスト・吹き出し編集
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- 【タブ2】コマ個別設定 (config_data) --}}
                    <div class="edit-slide-tab-pane d-none" id="edit-tab-pane-config">
                        <div class="p-2 border rounded bg-light">
                            <small class="text-muted d-block mb-3">このコマだけに適用したい表示・演出設定をカスタマイズできます。</small>

                            @if (!empty($slide_config_definitions))
                                @foreach ($slide_config_definitions as $key => $def)
                                    @php
                                        $isDisabled = ($def['premium'] ?? false) && !$is_premium;
                                    @endphp
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold mb-1">
                                            @if($def['premium'] ?? false)
                                                <i class="fa-solid fa-crown text-warning me-1"></i>
                                            @endif
                                            {{ $def['label'] }}
                                        </label>
                                        <select class="form-select form-select-sm slide-config-input" data-key="{{ $key }}" name="config_data[{{ $key }}]" {{ $isDisabled ? 'disabled' : '' }}>
                                            @foreach ($def['options'] as $val => $label)
                                                <option value="{{ $val }}">{{ $label }}</option>
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
                    <button type="button" class="col-5 btn btn-secondary" onclick="closeModal('life_theater_slide_edit-modal')">キャンセル</button>
                    <button type="submit" class="col-5 btn btn-primary">更新</button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
function switchEditSlideTab(tabName, btn) {
    document.querySelectorAll('.edit-slide-tab-btn').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    document.querySelectorAll('.edit-slide-tab-pane').forEach(p => p.classList.add('d-none'));
    const targetPane = document.getElementById('edit-tab-pane-' + tabName);
    if (targetPane) targetPane.classList.remove('d-none');
}

document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('life_theater_slide_edit-modal');
    if (editModal) {
        editModal.addEventListener('modal:open', function (e) {
            const urlInput = document.getElementById('edit_slide_media_url');
            const previewImg = document.getElementById('edit_slide_media_preview');
            const previewWrap = document.getElementById('edit_slide_media_preview_wrap');

            if (urlInput && urlInput.value && urlInput.value.trim() !== '') {
                previewImg.src = urlInput.value;
                previewWrap.classList.remove('d-none');
            } else {
                previewImg.src = '';
                previewWrap.classList.add('d-none');
            }

            // ★ 隠し要素または e.detail から config_data を復元
            const detailData = e.detail || {};
            const hiddenInput = document.getElementById('edit_slide_config_data');
            let rawConfig = hiddenInput ? hiddenInput.value : (detailData.edit_slide_config_data || '');

            let configObj = {};
            if (typeof rawConfig === 'object' && rawConfig !== null) {
                configObj = rawConfig;
            } else if (typeof rawConfig === 'string' && rawConfig.trim() !== '') {
                try {
                    let parsed = rawConfig;
                    while (typeof parsed === 'string') {
                        parsed = JSON.parse(parsed);
                    }
                    configObj = parsed || {};
                } catch(err) {
                    configObj = {};
                }
            }

            // ★ 各 select 要素へ初期選択値を確実にセット
            editModal.querySelectorAll('.slide-config-input').forEach(select => {
                const key = select.getAttribute('data-key');
                if (key && (key in configObj)) {
                    select.value = String(configObj[key]);
                } else {
                    select.selectedIndex = 0;
                }
            });

            switchEditSlideTab('basic', document.getElementById('edit-basic-tab-btn'));
        });
    }
});
</script>