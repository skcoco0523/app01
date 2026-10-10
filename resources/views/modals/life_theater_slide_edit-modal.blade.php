<div id="life_theater_slide_edit-modal" class="notification-overlay" onclick="closeModal('life_theater_slide_edit-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-content" style="max-height: 85vh; overflow: hidden;">
            
            <form id="slideEditForm" onsubmit="return false;">
                @csrf
                <input type="hidden" name="id" id="edit_slide_id" value="">
                <input type="hidden" name="life_theater_media_id" id="edit_slide_media_id" value="">
                <input type="hidden" id="edit_slide_media_url" value="">
                <input type="hidden" id="edit_slide_config_data" value="">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square"></i> スライドの編集</h5>
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
                                <i class="fa-solid fa-sliders me-1"></i> 個別設定
                            </button>
                        </li>
                    </ul>

                    {{-- 【タブ1】基本情報 --}}
                    <div class="edit-slide-tab-pane" id="edit-tab-pane-basic">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold mb-0">ラベル</label>
                                    <span class="text-muted" style="font-size: 10px;"><span id="edit_slide_label_count">0</span>/20</span>
                                </div>
                                <input type="text" class="form-control form-control-sm" id="edit_slide_label" name="label" maxlength="20" oninput="updateCharCount('edit_slide_label', 'edit_slide_label_count')">
                            </div>
                            <div class="col-6 mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold mb-0">日付</label>
                                    <span class="text-muted" style="font-size: 10px;"><span id="edit_slide_date_count">0</span>/20</span>
                                </div>
                                <input type="text" class="form-control form-control-sm" id="edit_slide_date" name="slide_date" maxlength="20" oninput="updateCharCount('edit_slide_date', 'edit_slide_date_count')">
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">タイトル <span class="text-danger">*</span></label>
                                <span class="text-muted" style="font-size: 10px;"><span id="edit_slide_title_count">0</span>/30</span>
                            </div>
                            <input type="text" class="form-control form-control-sm" id="edit_slide_title" name="title" maxlength="30" required oninput="updateCharCount('edit_slide_title', 'edit_slide_title_count')">
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">サブタイトル</label>
                                <span class="text-muted" style="font-size: 10px;"><span id="edit_slide_subtitle_count">0</span>/30</span>
                            </div>
                            <input type="text" class="form-control form-control-sm" id="edit_slide_subtitle" name="subtitle" maxlength="30" oninput="updateCharCount('edit_slide_subtitle', 'edit_slide_subtitle_count')">
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">説明文・メッセージ</label>
                                <span class="text-muted" style="font-size: 10px;"><span id="edit_slide_content_count">0</span>/200</span>
                            </div>
                            <textarea class="form-control form-control-sm" id="edit_slide_content" name="content" rows="3" maxlength="200" oninput="updateCharCount('edit_slide_content', 'edit_slide_content_count')"></textarea>
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
                                <small class="text-muted">人物や吹き出し編集</small>
                                <button type="button" class="btn btn-outline-primary btn-sm"
                                    onclick="
                                        const slideId = document.getElementById('edit_slide_id').value;
                                        openModal('life_theater_slide_object-modal', {
                                            object_target_slide_id: slideId
                                        });
                                    ">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> 吹き出し編集
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- 【タブ2】スライド個別設定 (config_data) --}}
                    <div class="edit-slide-tab-pane d-none" id="edit-tab-pane-config">
                        <div class="p-2 border rounded bg-light">
                            <small class="text-muted d-block mb-3">このスライドだけに適用したい表示・演出設定をカスタマイズできます。</small>

                            @if (!empty($slide_config_definitions))
                                @foreach ($slide_config_definitions as $key =>$def)
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
                                            @foreach ($def['options'] as $val =>$label)
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
                    <button type="button" id="saveSlideEditBtn" class="col-5 btn btn-primary" onclick="saveSlideEdit()">更新</button>
                </div>
            </form>

        </div>
    </div>
</div>

<script>
if (typeof window.updateCharCount !== 'function') {
    window.updateCharCount = function(inputId, countId) {
        const input = document.getElementById(inputId);
        const count = document.getElementById(countId);
        if (input && count) {
            count.textContent = input.value.length;
        }
    };
}

function refreshEditSlideCharCounts() {
    updateCharCount('edit_slide_label', 'edit_slide_label_count');
    updateCharCount('edit_slide_date', 'edit_slide_date_count');
    updateCharCount('edit_slide_title', 'edit_slide_title_count');
    updateCharCount('edit_slide_subtitle', 'edit_slide_subtitle_count');
    updateCharCount('edit_slide_content', 'edit_slide_content_count');
}

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

            editModal.querySelectorAll('.slide-config-input').forEach(select => {
                const key = select.getAttribute('data-key');
                if (key && (key in configObj)) {
                    select.value = String(configObj[key]);
                } else {
                    select.selectedIndex = 0;
                }
            });

            switchEditSlideTab('basic', document.getElementById('edit-basic-tab-btn'));
            
            // モーダル表示時に初期データの文字数を更新
            setTimeout(refreshEditSlideCharCounts, 50);
        });
    }
});

// スライド非同期保存処理 (Ajax)
async function saveSlideEdit() {
    const slideId = document.getElementById('edit_slide_id').value;
    const btn = document.getElementById('saveSlideEditBtn');

    if (!slideId) return;

    btn.disabled = true;

    const form = document.getElementById('slideEditForm');
    const formData = new FormData(form);

    try {
        const response = await fetch("{{ route('api.life_theater.slide.update') }}", {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        });

        const result = await response.json();
        if (result.status === 'success') {
            showNotification( "保存しました。","success",2000);
            
            if (result.data) {
                updateSlideDOM(result.data);
            }
        } else {
            showNotification( "更新に失敗しました。","error",2000);
        }
    } catch (e) {
        console.error(e);
        showNotification( "更新処理中にエラーが発生しました。","error",2000);
    } finally {
        btn.disabled = false;
    }
}

// 親画面（show.blade.php）のDOM要素を更新する関数
function updateSlideDOM(slide) {
    const editBtn = document.querySelector(`button[data-slide*='"edit_slide_id":"${slide.id}"']`) ||
                    document.querySelector(`button[data-slide*='"edit_slide_id":${slide.id}']`);
    
    if (!editBtn) return;

    const updatedData = {
        'edit_slide_id': String(slide.id),
        'edit_slide_label': String(slide.label || ''),
        'edit_slide_slide_date': String(slide.slide_date || ''),
        'edit_slide_title': String(slide.title || ''),
        'edit_slide_subtitle': String(slide.subtitle || ''),
        'edit_slide_content': String(slide.content || ''),
        'edit_slide_media_id': String(slide.life_theater_media_id || ''),
        'edit_slide_media_name': String((slide.media && slide.media.name) ? slide.media.name : '未設定'),
        'edit_slide_media_url': String((slide.media && slide.media.image_s3_key) ? slide.media.image_s3_key : ''),
        'edit_slide_config_data': slide.parsed_config || {}
    };
    editBtn.dataset.slide = JSON.stringify(updatedData);

    const cardBody = editBtn.closest('.card-body');
    if (cardBody) {
        const labelBadge = cardBody.querySelector('.badge.bg-info');
        if (labelBadge) {
            if (slide.label) {
                labelBadge.textContent = slide.label;
                labelBadge.classList.remove('d-none');
            } else {
                labelBadge.classList.add('d-none');
            }
        }

        const titleEl = cardBody.querySelector('strong');
        if (titleEl) titleEl.textContent = slide.title || 'タイトルなし';

        const subTitleEl = cardBody.querySelector('small.text-muted');
        if (subTitleEl) {
            if (slide.subtitle) {
                subTitleEl.textContent = slide.subtitle;
                subTitleEl.classList.remove('d-none');
            } else {
                subTitleEl.classList.add('d-none');
            }
        }

        let mediaBadge = cardBody.querySelector('.badge.bg-success');
        if (slide.media && slide.media.image_s3_key) {
            if (!mediaBadge) {
                const targetWrap = cardBody.querySelector('.d-flex.align-items-center.gap-2.flex-shrink-0');
                if (targetWrap) {
                    mediaBadge = document.createElement('span');
                    mediaBadge.className = 'badge bg-success d-inline-flex align-items-center justify-content-center';
                    mediaBadge.style.cssText = 'height: 32px; width: 32px; padding: 0;';
                    mediaBadge.innerHTML = '<i class="fa-solid fa-image"></i>';
                    targetWrap.insertBefore(mediaBadge, targetWrap.firstChild);
                }
            }
        } else if (mediaBadge) {
            mediaBadge.remove();
        }
    }
}
</script>