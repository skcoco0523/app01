<div id="life_theater_slide_object-modal" class="notification-overlay" onclick="closeModal('life_theater_slide_object-modal')">
    <div class="notification-modal" style="max-width: 650px; width: 95%;" onclick="event.stopPropagation()">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-comments me-1"></i> キャスト・吹き出しの編集
                </h5>
                <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('life_theater_slide_object-modal')"></button>
            </div>

            <div class="modal-body p-3">
                {{-- 対象スライドIDの保持 --}}
                <input type="hidden" id="object_target_slide_id" value="">

                {{-- オブジェクト一覧エリア --}}
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
                        <span class="fw-bold small" id="object_form_title"><i class="fa-solid fa-plus-circle me-1"></i> 新規オブジェクト追加</span>
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none d-none" id="object_reset_btn" onclick="resetObjectForm()">
                            キャンセル（新規作成へ）
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

                    <div class="text-end">
                        <button type="button" id="saveObjectBtn" class="btn btn-primary btn-sm px-3" onclick="saveSlideObject()">
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

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('life_theater_slide_object-modal');
    if (modal) {
        modal.addEventListener('modal:open', function () {
            const slideId = document.getElementById('object_target_slide_id').value;
            resetObjectForm();
            if (slideId) {
                loadSlideObjects(slideId);
            }
        });
    }
});

// オブジェクト一覧の取得・描画
async function loadSlideObjects(slideId) {
    const listContainer = document.getElementById('slide_object_list');
    listContainer.innerHTML = '<div class="text-center text-muted py-2 small">読み込み中...</div>';

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
        } else {
            listContainer.innerHTML = '<div class="text-center text-danger py-2 small">取得に失敗しました。</div>';
        }
    } catch (e) {
        console.error(e);
        listContainer.innerHTML = '<div class="text-center text-danger py-2 small">エラーが発生しました。</div>';
    }
}

// 種別の英字キーを日本語表示用に変換するマッピングテーブル
const TYPE_LABELS = {
    cast: '💬 人物セリフ',
    narration: '📝 補足テロップ',
    event: '🎉 イベントカード',
    character: '💬 人物セリフ',
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

    listContainer.innerHTML = '';
    objects.forEach(obj => {
        const item = document.createElement('div');
        item.className = 'd-flex align-items-center justify-content-between p-2 bg-white rounded border shadow-sm';

        const mediaUrl = obj.media ? obj.media.image_s3_key : '';
        const imgTag = mediaUrl 
            ? `<img src="${mediaUrl}" class="rounded-circle border me-2" style="width: 32px; height: 32px; object-fit: cover;">`
            : `<div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2 small" style="width: 32px; height: 32px;"><i class="fa-solid fa-user"></i></div>`;

        // ▼ 種別の日本語ラベルを取得 ▼
        const displayTypeLabel = TYPE_LABELS[obj.type] || obj.type || 'その他';

        item.innerHTML = `
            <div class="d-flex align-items-center overflow-hidden me-2">
                ${imgTag}
                <div class="text-truncate">
                    <strong class="small d-block text-truncate">
                        ${obj.name || '名称なし'} 
                        <span class="badge bg-light text-secondary fw-normal border ms-1">${displayTypeLabel}</span>
                    </strong>
                    <small class="text-muted text-truncate d-block" style="font-size: 11px;">${obj.text || '（テキストなし）'}</small>
                </div>
            </div>
            <div class="d-flex gap-1 flex-shrink-0">
                <button type="button" class="btn btn-outline-primary btn-sm p-1 px-2" onclick="editSlideObjectItem(${obj.id})"><i class="fa-solid fa-pen"></i></button>
                <button type="button" class="btn btn-outline-danger btn-sm p-1 px-2" onclick="deleteSlideObjectItem(${obj.id})"><i class="fa-solid fa-trash"></i></button>
            </div>
        `;
        listContainer.appendChild(item);
    });
}

// 編集状態のセット
function editSlideObjectItem(objectId) {
    const obj = currentSlideObjects.find(o => o.id === objectId);
    if (!obj) return;

    document.getElementById('object_edit_id').value = obj.id;
    document.getElementById('object_type').value = obj.type || 'cast';
    document.getElementById('object_name').value = obj.name || '';
    document.getElementById('object_text').value = obj.text || '';
    document.getElementById('object_media_id').value = obj.life_theater_media_id || '';

    // メディアプレビュー
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

    document.getElementById('object_form_title').innerHTML = '<i class="fa-solid fa-pen me-1"></i> オブジェクトの編集';
    document.getElementById('saveObjectBtn').textContent = '更新する';
    document.getElementById('object_reset_btn').classList.remove('d-none');
}

// フォームのリセット
function resetObjectForm() {
    document.getElementById('object_edit_id').value = '';
    document.getElementById('object_media_id').value = '';
    document.getElementById('slideObjectForm').reset();

    document.getElementById('object_media_name').textContent = '未選択';
    document.getElementById('object_media_preview_wrap').classList.add('d-none');
    document.getElementById('object_media_preview').src = '';

    document.getElementById('object_form_title').innerHTML = '<i class="fa-solid fa-plus-circle me-1"></i> 新規オブジェクト追加';
    document.getElementById('saveObjectBtn').textContent = '追加する';
    document.getElementById('object_reset_btn').classList.add('d-none');
}

// オブジェクトの保存（作成 / 更新）
async function saveSlideObject() {
    const slideId = document.getElementById('object_target_slide_id').value;
    const editId = document.getElementById('object_edit_id').value;
    const btn = document.getElementById('saveObjectBtn');

    if (!slideId) return;

    btn.disabled = true;

    const payload = {
        id: editId || null,
        life_theater_slide_id: slideId,
        type: document.getElementById('object_type').value,
        name: document.getElementById('object_name').value,
        text: document.getElementById('object_text').value,
        life_theater_media_id: document.getElementById('object_media_id').value || null
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
            resetObjectForm();
            loadSlideObjects(slideId);
        } else {
            alert(result.message || '保存に失敗しました。');
        }
    } catch (e) {
        console.error(e);
        alert('保存処理中にエラーが発生しました。');
    } finally {
        btn.disabled = false;
    }
}

// オブジェクトの削除（共通モーダル呼び出し）
function deleteSlideObjectItem(objectId) {
    openModal('common-modal', {
        title: 'オブジェクト削除',
        mess: 'このキャスト・吹き出しを削除しますか？',
        cancel_btn: 'キャンセル',
        confirm_btn: '削除',
        user_chk: false,
        onConfirm: function() {
            executeDeleteSlideObjectItem(objectId);
        }
    });
}

// 実際の削除処理（Ajax）
async function executeDeleteSlideObjectItem(objectId) {
    const slideId = document.getElementById('object_target_slide_id').value;

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
            loadSlideObjects(slideId);
        } else {
            alert(result.message || '削除に失敗しました。');
        }
    } catch (e) {
        console.error(e);
        alert('削除処理中にエラーが発生しました。');
    }
}
</script>