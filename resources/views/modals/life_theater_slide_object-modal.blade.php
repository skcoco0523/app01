<div id="life_theater_slide_object-modal" class="notification-overlay" onclick="closeModal('life_theater_slide_object-modal')">
    <div class="notification-modal" style="max-width: 650px; width: 95%;" onclick="event.stopPropagation()">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-comments me-1"></i> キャスト・吹き出しの編集
                </h5>
                <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('life_theater_slide_object-modal')"></button>
            </div>

            <div class="modal-body p-3" style="max-height: 62vh; overflow-y: auto;">
                {{-- 対象スライドIDの保持 --}}
                <input type="hidden" id="object_target_slide_id" value="">

                {{-- 一覧エリア --}}
                <div class="mb-3">
                    <label class="form-label fw-bold small mb-1">登録済みのキャスト・吹き出し</label>
                    <div id="slide_object_list" class="d-flex flex-column gap-2 overflow-auto p-2 bg-light rounded border" style="max-height: 200px;">
                        <div class="text-center text-muted py-2 small">読み込み中...</div>
                    </div>
                </div>

                <hr class="my-3">

                {{-- オブジェクト追加・編集フォーム --}}
                <form id="slideObjectForm" onsubmit="return false;">
                    <input type="hidden" id="object_edit_id" value="">
                    <input type="hidden" id="object_media_id" value="">

                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-bold small" id="object_form_title">
                            <i class="fa-solid fa-plus-circle me-1"></i> 新規追加
                        </span>
                        <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 d-none" id="object_reset_btn" onclick="resetObjectForm()">
                            <i class="fa-solid fa-plus me-1"></i> 別の新規追加へ
                        </button>
                    </div>

                    <div class="row g-2 mb-2">
                        {{-- 種別 --}}
                        <div class="col-6">
                            <label class="form-label small mb-1">種別</label>
                            <select class="form-select form-select-sm" id="object_type">
                                <option value="cast">💬 人物のセリフ（アイコン＋吹き出し）</option>
                                <option value="narration">📝 補足テロップ（字幕風）</option>
                                <option value="event">🎉 記念日・イベントカード</option>
                            </select>
                        </div>
                        {{-- 表示名 --}}
                        <div class="col-6">
                            <label class="form-label small mb-1">名前・ラベル</label>
                            <input type="text" class="form-control form-control-sm" id="object_name" placeholder="例: ななえ, メモ1">
                        </div>
                    </div>

                    {{-- テキスト --}}
                    <div class="mb-2">
                        <label class="form-label small mb-1">セリフ・メッセージ</label>
                        <textarea class="form-control form-control-sm" id="object_text" rows="2" placeholder="セリフやテキストを入力"></textarea>
                    </div>

                    {{-- アイコン / プロフィール画像選択 --}}
                    <div class="mb-3">
                        <label class="form-label small mb-1">プロフィール・吹き出し画像</label>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                onclick="
                                    targetMediaInputId = 'object_media_id';
                                    openModal('life_theater_media-modal', {
                                        media_manage_mode: '0',
                                        media_modal_title_text: '画像を選択'
                                    });
                                ">
                                ライブラリから選択
                            </button>
                            <span id="object_media_name" class="small text-muted">未選択</span>
                            
                            {{-- プレビュー表示 --}}
                            <div id="object_media_preview_wrap" class="d-none ms-auto">
                                <img id="object_media_preview" src="" class="rounded-circle border" style="width: 32px; height: 32px; object-fit: cover;">
                            </div>
                        </div>
                    </div>

                    {{-- 個別演出設定 (config_data) エリア --}}
                    <div class="mb-3 border rounded p-2 bg-light">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <small class="fw-bold text-secondary mb-0">
                                <i class="fa-solid fa-sliders me-1"></i> キャスト個別演出設定
                            </small>
                        </div>

                        @if (!empty($object_config_definitions))
                            <div class="row g-2">
                                @foreach ($object_config_definitions as $key =>$def)
                                    @php
                                        $isDisabled = ($def['premium'] ?? false) && !($is_premium ?? false);
                                    @endphp
                                    <div class="col-6">
                                        <label class="form-label small mb-1" style="font-size: 11px;">
                                            @if($def['premium'] ?? false)
                                                <i class="fa-solid fa-crown text-warning me-1" title="プレミアム機能"></i>
                                            @endif
                                            {{ $def['label'] }}
                                        </label>
                                        <select class="form-select form-select-sm object-config-input" 
                                                data-key="{{ $key }}" 
                                                id="object_config_{{ $key }}" 
                                                {{ $isDisabled ? 'disabled' : '' }}>
                                            @foreach ($def['options'] as $val =>$label)
                                                <option value="{{ $val }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>

                            @if (!($is_premium ?? false))
                                <small class="text-muted d-block mt-2" style="font-size: 10px;">
                                    <i class="fa-solid fa-circle-info me-1"></i> 王冠マークの項目はプレミアムパック限定設定です。
                                </small>
                            @endif
                        @endif
                    </div>

                    {{-- ボタン＆保存完了メッセージ表示領域 --}}
                    <div class="d-flex align-items-center justify-content-end gap-2">
                        <span id="save_success_badge" class="badge bg-success-subtle text-success border border-success d-none">
                            <i class="fa-solid fa-check me-1"></i> 更新完了
                        </span>
                        <button type="button" id="saveObjectBtn" class="btn btn-primary btn-sm px-4" onclick="saveSlideObject()">
                            追加する
                        </button>
                    </div>
                </form>
            </div>

            <div class="modal-footer p-2 justify-content-center">
                <button type="button" class="btn btn-secondary btn-sm col-4" onclick="closeModal('life_theater_slide_object-modal')">閉じる</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentSlideObjects = [];
let savedListScrollTop = 0;
let savedModalBodyScrollTop = 0;

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('life_theater_slide_object-modal');
    if (modal) {
        modal.addEventListener('modal:open', function () {
            const slideId = document.getElementById('object_target_slide_id').value;
            savedListScrollTop = 0;
            savedModalBodyScrollTop = 0;
            resetObjectForm();
            if (slideId) {
                loadSlideObjects(slideId);
            }
        });
    }
});

function captureScrollPositions() {
    const listContainer = document.getElementById('slide_object_list');
    const modalBody = document.querySelector('#life_theater_slide_object-modal .modal-body');
    if (listContainer) savedListScrollTop = listContainer.scrollTop;
    if (modalBody) savedModalBodyScrollTop = modalBody.scrollTop;
}

function restoreScrollPositions() {
    const listContainer = document.getElementById('slide_object_list');
    const modalBody = document.querySelector('#life_theater_slide_object-modal .modal-body');
    if (listContainer) listContainer.scrollTop = savedListScrollTop;
    if (modalBody) modalBody.scrollTop = savedModalBodyScrollTop;
}

// オブジェクト一覧の取得・描画
async function loadSlideObjects(slideId, keepScroll = false) {
    const listContainer = document.getElementById('slide_object_list');
    if (!keepScroll) {
        listContainer.innerHTML = '<div class="text-center text-muted py-2 small">読み込み中...</div>';
    }

    try {
        const requestUrl = "{{ route('api.life_theater.slide_object.index') }}?slide_id=" + slideId;
        const response = await fetch(requestUrl, {
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const result = await response.json();

        if (result.status === 'success') {
            currentSlideObjects = result.data || [];
            renderSlideObjectList(currentSlideObjects);

            if (keepScroll) {
                restoreScrollPositions();
            }
        } else {
            listContainer.innerHTML = '<div class="text-center text-danger py-2 small">取得に失敗しました。</div>';
        }
    } catch (e) {
        console.error(e);
        listContainer.innerHTML = '<div class="text-center text-danger py-2 small">エラーが発生しました。</div>';
    }
}

const TYPE_LABELS = {
    cast: '💬 人物のセリフ',
    narration: '📝 補足テロップ',
    event: '🎉 イベントカード',
    character: '💬 人物のセリフ',
    bubble: '💬 吹き出し',
    caption: '📝 テロップ',
    stamp: '🎉 スタンプ'
};

function renderSlideObjectList(objects) {
    const listContainer = document.getElementById('slide_object_list');
    if (!objects || objects.length === 0) {
        listContainer.innerHTML = '<div class="text-center text-muted py-2 small">登録されたキャスト・吹き出しはありません。</div>';
        return;
    }

    const activeEditId = document.getElementById('object_edit_id').value;

    listContainer.innerHTML = '';
    objects.forEach(obj => {
        const item = document.createElement('div');
        const isEditingThis = activeEditId && String(obj.id) === String(activeEditId);
        
        item.className = `d-flex align-items-center justify-content-between p-2 rounded border shadow-sm ${isEditingThis ? 'bg-primary-subtle border-primary' : 'bg-white'}`;
        item.id = `object_row_${obj.id}`;

        const mediaUrl = obj.media ? obj.media.image_s3_key : '';
        const imgTag = mediaUrl 
            ? `<img src="${mediaUrl}" class="rounded-circle border me-2" style="width: 32px; height: 32px; object-fit: cover;">`
            : `<div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2 small" style="width: 32px; height: 32px;"><i class="fa-solid fa-user"></i></div>`;

        const displayTypeLabel = TYPE_LABELS[obj.type] || obj.type || 'その他';

        item.innerHTML = `
            <div class="d-flex align-items-center overflow-hidden me-2">
                ${imgTag}
                <div class="text-truncate">
                    <strong class="small d-block text-truncate">
                        ${obj.name || '名称なし'} 
                        <span class="badge bg-light text-secondary fw-normal border ms-1">${displayTypeLabel}</span>
                        ${isEditingThis ? '<span class="badge bg-primary ms-1">編集中</span>' : ''}
                    </strong>
                    <small class="text-muted text-truncate d-block" style="font-size: 11px;">${obj.text || '（テキストなし）'}</small>
                </div>
            </div>
            <div class="d-flex gap-1 flex-shrink-0">
                <button type="button" class="btn ${isEditingThis ? 'btn-primary' : 'btn-outline-primary'} btn-sm p-1 px-2" onclick="editSlideObjectItem(${obj.id})">
                    <i class="fa-solid fa-pen"></i>
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="deleteSlideObjectItem(${obj.id})">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        `;
        listContainer.appendChild(item);
    });
}

function editSlideObjectItem(objectId) {
    const obj = currentSlideObjects.find(o => o.id === objectId);
    if (!obj) return;

    captureScrollPositions();

    document.getElementById('object_edit_id').value = obj.id;
    document.getElementById('object_type').value = obj.type || 'cast';
    document.getElementById('object_name').value = obj.name || '';
    document.getElementById('object_text').value = obj.text || '';
    document.getElementById('object_media_id').value = obj.life_theater_media_id || '';

    const previewWrap = document.getElementById('object_media_preview_wrap');
    const previewImg = document.getElementById('object_media_preview');
    const mediaNameSpan = document.getElementById('object_media_name');

    if (obj.media && obj.media.image_s3_key) {
        previewImg.src = obj.media.image_s3_key;
        previewWrap.classList.remove('d-none');
        mediaNameSpan.textContent = obj.media.name || '選択済み';
    } else {
        previewWrap.classList.add('d-none');
        mediaNameSpan.textContent = '未選択';
    }

    const configObj = obj.parsed_config || obj.config_data || {};
    document.querySelectorAll('.object-config-input').forEach(select => {
        const key = select.getAttribute('data-key');
        if (key && (key in configObj)) {
            select.value = String(configObj[key]);
        } else {
            select.selectedIndex = 0;
        }
    });

    document.getElementById('object_form_title').innerHTML = '<i class="fa-solid fa-pen me-1 text-primary"></i> 編集中';
    document.getElementById('saveObjectBtn').textContent = '更新する';
    document.getElementById('saveObjectBtn').className = 'btn btn-success btn-sm px-4';
    document.getElementById('object_reset_btn').classList.remove('d-none');

    renderSlideObjectList(currentSlideObjects);
    restoreScrollPositions();
}

function resetObjectForm() {
    document.getElementById('object_edit_id').value = '';
    document.getElementById('object_media_id').value = '';
    document.getElementById('slideObjectForm').reset();

    document.getElementById('object_media_name').textContent = '未選択';
    document.getElementById('object_media_preview_wrap').classList.add('d-none');
    document.getElementById('object_media_preview').src = '';

    document.querySelectorAll('.object-config-input').forEach(select => {
        select.selectedIndex = 0;
    });

    document.getElementById('object_form_title').innerHTML = '<i class="fa-solid fa-plus-circle me-1"></i> 新規追加';
    document.getElementById('saveObjectBtn').textContent = '追加する';
    document.getElementById('saveObjectBtn').className = 'btn btn-primary btn-sm px-4';
    document.getElementById('object_reset_btn').classList.add('d-none');

    if (currentSlideObjects.length > 0) {
        renderSlideObjectList(currentSlideObjects);
    }
}

async function saveSlideObject() {
    const slideId = document.getElementById('object_target_slide_id').value;
    const editId = document.getElementById('object_edit_id').value;
    const btn = document.getElementById('saveObjectBtn');

    if (!slideId) return;

    captureScrollPositions();
    btn.disabled = true;

    const configData = {};
    document.querySelectorAll('.object-config-input').forEach(select => {
        const key = select.getAttribute('data-key');
        if (key) {
            configData[key] = select.value;
        }
    });

    const payload = {
        id: editId || null,
        life_theater_slide_id: slideId,
        type: document.getElementById('object_type').value,
        name: document.getElementById('object_name').value,
        text: document.getElementById('object_text').value,
        life_theater_media_id: document.getElementById('object_media_id').value || null,
        config_data: configData
    };

    try {
        const response = await fetch("{{ route('api.life_theater.slide_object.save') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (result.status === 'success') {
            if (editId) {
                const successBadge = document.getElementById('save_success_badge');
                if (successBadge) {
                    successBadge.classList.remove('d-none');
                    setTimeout(() => successBadge.classList.add('d-none'), 2500);
                }
            } else {
                resetObjectForm();
            }

            await loadSlideObjects(slideId, true);
            showNotification(editId ? "更新しました。" : "追加しました。", "success", 2000);
        } else {
            showNotification("保存に失敗しました。", "error", 2000);
        }
    } catch (e) {
        console.error(e);
        showNotification("保存処理中にエラーが発生しました。", "error", 2000);
    } finally {
        btn.disabled = false;
    }
}

function deleteSlideObjectItem(objectId) {
    openModal('common-modal', {
        title: '削除',
        mess: 'このキャスト・吹き出しを削除しますか？',
        cancel_btn: 'キャンセル',
        confirm_btn: '削除',
        user_chk: false,
        onConfirm: function() {
            executeDeleteSlideObjectItem(objectId);
        }
    });
}

async function executeDeleteSlideObjectItem(objectId) {
    const slideId = document.getElementById('object_target_slide_id').value;
    const activeEditId = document.getElementById('object_edit_id').value;

    captureScrollPositions();

    try {
        const response = await fetch("{{ route('api.life_theater.slide_object.destroy') }}", {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ id: objectId })
        });

        const result = await response.json();
        if (result.status === 'success') {
            if (activeEditId && String(activeEditId) === String(objectId)) {
                resetObjectForm();
            }
            await loadSlideObjects(slideId, true);
            showNotification("削除しました。", "success", 2000);
        } else {
            showNotification("削除に失敗しました。", "error", 2000);
        }
    } catch (e) {
        console.error(e);
        showNotification("削除処理中にエラーが発生しました。", "error", 2000);
    }
}
</script>