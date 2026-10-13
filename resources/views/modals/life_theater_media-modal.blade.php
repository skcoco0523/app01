<!-- Cropper.js の CSS と JS（CDN） -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>

<div id="life_theater_media-modal" class="notification-overlay" onclick="closeModal('life_theater_media-modal')">
    <div class="notification-modal" style="max-width: 600px; width: 90%;" onclick="event.stopPropagation()">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-images me-1"></i> <span id="media_modal_title_text">画像ライブラリ</span>
                </h5>
                {{-- 枚数表示用のバッジ --}}
                <span id="media_limit_badge" class="badge bg-secondary ms-3 d-none" style="font-size: 0.75rem;">
                    使用中: - / - 枚
                </span>
                <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('life_theater_media-modal')"></button>
            </div>
            
            {{-- 管理モード判定用の隠しフィールド --}}
            <input type="hidden" id="media_manage_mode" value="0">

            <div class="modal-body p-3">
                <ul class="nav nav-tabs mb-3">
                    <li class="nav-item">
                        <button class="nav-link active btn-sm" id="media-list-tab" type="button" onclick="switchMediaTab('list')">一覧</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link btn-sm" id="media-upload-tab" type="button" onclick="switchMediaTab('upload')">新規アップロード</button>
                    </li>
                </ul>

                {{-- 一覧表示エリア --}}
                <div id="media-list-pane">
                    <div id="media_library_grid" class="d-flex flex-wrap gap-2 overflow-auto" style="max-height: 300px;"></div>
                </div>

                {{-- トリミング・アップロード・編集表示エリア --}}
                <div id="media-upload-pane" class="d-none">
                    
                    {{-- 1. 名前（ラベル）の変更専用エリア（一覧選択時のみ表示） --}}
                    <div id="media_rename_area" class="mb-3 p-2 border rounded bg-light d-none">
                        <label class="form-label fw-bold small mb-1">登録名の変更</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="media_edit_name_input" placeholder="画像名を入力">
                            <button type="button" class="btn btn-outline-primary" id="mediaRenameBtn" onclick="updateMediaNameOnly()">
                                <i class="fa-solid fa-pen"></i> 名前を変更
                            </button>
                        </div>
                    </div>

                    <form id="mediaUploadForm" onsubmit="return false;">
                        @csrf
                        {{-- 新規アップロード用の名前入力欄 --}}
                        <div class="mb-2" id="new_media_name_container">
                            <label class="form-label fw-bold small mb-1">画像名・ラベル</label>
                            <input type="text" class="form-control form-control-sm" id="media_upload_name" placeholder="例: 主人公, 背景1">
                        </div>

                        {{-- ファイル選択 --}}
                        <div class="mb-2" id="new_media_file_container">
                            <label class="form-label fw-bold small mb-1" id="file_input_label">画像ファイル <span class="text-danger">*</span></label>
                            <input type="file" class="form-control form-control-sm" id="media_upload_file" accept="image/*" onchange="initCropperFromFile(this)">
                        </div>

                        {{-- 枠拡張エリア --}}
                        <div id="media_expand_area" class="mt-3 p-2 border border-warning rounded bg-light text-center d-none">
                            <small class="text-danger fw-bold d-block mb-1">
                                <i class="fa-solid fa-triangle-exclamation"></i> 登録上限に達しています
                            </small>
                            <button type="button" class="btn btn-warning btn-sm fw-bold w-100" onclick="confirmExpandMediaLimit()">
                                <i class="fa-solid fa-cart-plus me-1"></i> 100ptで枠を拡張する（+10枚）
                            </button>
                        </div>

                        {{-- トリミングプレビュー表示部 --}}
                        <div id="cropper_container" class="mb-3 d-none text-center bg-light p-2 border rounded">
                            <label class="form-label fw-bold small mb-1 d-block text-start">トリミング調整</label>
                            <div style="max-height: 220px; overflow: hidden;">
                                <img id="cropper_target_image" src="" style="max-width: 100%; display: block;">
                            </div>
                            <small class="text-muted d-block mt-1">枠をドラッグして選択範囲を調整してください</small>
                        </div>

                        <div class="text-end">
                            <button type="button" id="mediaUploadSubmitBtn" class="btn btn-primary btn-sm" onclick="uploadCroppedMediaFile()">
                                アップロード
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer row justify-content-center m-0 p-2">
                <button type="button" class="col-5 btn btn-secondary btn-sm" onclick="closeModal('life_theater_media-modal')">閉じる</button>
            </div>
        </div>
    </div>
</div>

<script>
let targetMediaInputId = null;
let cropperInstance = null;
let currentEditMediaItem = null; // 一覧から選択中の画像データ

document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('life_theater_media-modal');
    if (modal) {
        modal.addEventListener('modal:open', function () {
            switchMediaTab('list');
            loadMediaLibraryList();
        });
    }
});

function openMediaLibraryModal(inputId) {
    targetMediaInputId = inputId;
    openModal('life_theater_media-modal', {
        media_manage_mode: '0',
        media_modal_title_text: '画像を選択'
    });
}

// タブ切り替え処理
function switchMediaTab(tabName) {
    const listTab = document.getElementById('media-list-tab');
    const uploadTab = document.getElementById('media-upload-tab');
    const listPane = document.getElementById('media-list-pane');
    const uploadPane = document.getElementById('media-upload-pane');

    if (tabName === 'list') {
        listTab.classList.add('active');
        uploadTab.classList.remove('active');
        listPane.classList.remove('d-none');
        uploadPane.classList.add('d-none');
        destroyCropper();
        currentEditMediaItem = null;
    } else {
        uploadTab.classList.add('active');
        listTab.classList.remove('active');
        uploadPane.classList.remove('d-none');
        listPane.classList.add('d-none');

        // 手動で新規アップロードタブを開いた場合の初期化
        if (!currentEditMediaItem) {
            uploadTab.textContent = '新規アップロード';
            document.getElementById('media_rename_area').classList.add('d-none');
            document.getElementById('new_media_name_container').classList.remove('d-none');
            document.getElementById('new_media_file_container').classList.remove('d-none');
            document.getElementById('file_input_label').innerHTML = '画像ファイル <span class="text-danger">*</span>';
            
            // ★追加: 上限到達判定
            const badge = document.getElementById('media_limit_badge');
            const submitBtn = document.getElementById('mediaUploadSubmitBtn');
            if (badge && badge.classList.contains('bg-danger')) {
                submitBtn.textContent = '上限に達しています';
                submitBtn.disabled = true;
            } else {
                submitBtn.textContent = 'アップロード';
                submitBtn.disabled = false;
            }
            
            document.getElementById('media_upload_name').value = '';
            document.getElementById('media_upload_file').value = '';
        }
    }
}

// 画像一覧の取得と描画
async function loadMediaLibraryList() {
    const grid = document.getElementById('media_library_grid');
    const theaterId = "{{ $theater->id ?? $theater->life_theater_id ?? '' }}";
    const isManage = document.getElementById('media_manage_mode').value === '1';
    
    if (!theaterId) return;

    grid.innerHTML = '<div class="text-center w-100 py-3 text-muted">読み込み中...</div>';

    const indexUrlTemplate = "{{ route('api.life_theater.media.index', ['life_theater_id' => '___ID___']) }}";
    const requestUrl = indexUrlTemplate.replace('___ID___', theaterId);

    try {
        const response = await fetch(requestUrl, {
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const result = await response.json();

        if (result.status === 'success') {
            
            // ==========================================
            // 上限バッジの更新とアップロード制御
            // ==========================================
            const badge = document.getElementById('media_limit_badge');
            const submitBtn = document.getElementById('mediaUploadSubmitBtn');
            const uploadTab = document.getElementById('media-upload-tab');

            if (badge && result.current_count !== undefined && result.max_limit !== undefined) {
                badge.classList.remove('d-none');
                badge.textContent = `使用中: ${result.current_count} / ${result.max_limit} 枚`;

                // 上限に達している場合のUI変更
                if (result.current_count >= result.max_limit) {
                    badge.classList.remove('bg-secondary');
                    badge.classList.add('bg-danger');
                    
                    if (!currentEditMediaItem && submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = '上限に達しています';
                    }
                    // 拡張ボタンエリアを表示
                    document.getElementById('media_expand_area').classList.remove('d-none');
                } else {
                    badge.classList.remove('bg-danger');
                    badge.classList.add('bg-secondary');
                    if (!currentEditMediaItem && submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'アップロード';
                    }
                    // 拡張ボタンエリアを隠す
                    document.getElementById('media_expand_area').classList.add('d-none');
                }
            }
            // ==========================================

            if (result.data.length > 0) {
                grid.innerHTML = '';
                result.data.forEach(item => {
                    const card = document.createElement('div');
                    card.className = 'border rounded p-1 text-center position-relative shadow-sm';
                    card.style.width = '100px';

                    const imgArea = document.createElement('div');
                    imgArea.className = 'cursor-pointer';
                    imgArea.innerHTML = `
                        <img src="${item.image_s3_key}" class="img-fluid rounded mb-1" style="height: 70px; object-fit: cover; width: 100%;">
                        <small class="d-block text-truncate small" title="${item.name || ''}">${item.name || '名称なし'}</small>
                    `;

                    if (isManage) {
                        // 管理モード時：クリックで編集画面へ
                        imgArea.onclick = () => editExistingMedia(item);

                        // 削除ボタン
                        const deleteBtn = document.createElement('button');
                        deleteBtn.className = 'btn btn-danger btn-sm position-absolute top-0 end-0 m-1 p-0 d-flex align-items-center justify-content-center rounded-circle';
                        deleteBtn.style.width = '22px';
                        deleteBtn.style.height = '22px';
                        deleteBtn.innerHTML = '<i class="fa-solid fa-times" style="font-size: 11px;"></i>';
                        deleteBtn.onclick = (e) => {
                            e.stopPropagation();
                            deleteMediaItem(item.id);
                        };
                        card.appendChild(deleteBtn);
                    } else {
                        // 選択モード時
                        imgArea.onclick = () => selectMediaItem(item.id, item.name || '画像ID:' + item.id, item.image_s3_key);
                    }

                    card.appendChild(imgArea);
                    grid.appendChild(card);
                });
            } else {
                grid.innerHTML = '<div class="text-center w-100 py-3 text-muted">登録済みの画像がありません。<br>「新規アップロード」から登録してください。</div>';
            }
        } else {
            grid.innerHTML = '<div class="text-center w-100 py-3 text-danger">画像の取得に失敗しました。</div>';
        }
    } catch (e) {
        console.error(e);
        grid.innerHTML = '<div class="text-center w-100 py-3 text-danger">通信エラーが発生しました。</div>';
    }
}

// 既存画像を一覧から選択したときの編集状態
function editExistingMedia(item) {
    currentEditMediaItem = item;

    // UIの切り替え
    document.getElementById('media-upload-tab').textContent = '編集・トリミング';
    document.getElementById('media_rename_area').classList.remove('d-none'); // 名前変更欄を表示
    document.getElementById('new_media_name_container').classList.add('d-none'); // 新規用入力欄を非表示
    document.getElementById('media_edit_name_input').value = item.name || '';
    
    document.getElementById('file_input_label').textContent = '別の画像ファイルから切り出す場合';
    document.getElementById('mediaUploadSubmitBtn').textContent = 'トリミングして新規保存';

    switchMediaTab('upload');

    // Cropper で読み込み
    initCropperFromUrl(item.image_s3_key);
}

// DB上の名前（ラベル）のみを変更する処理 (Ajax)
async function updateMediaNameOnly() {
    if (!currentEditMediaItem) return;

    const newName = document.getElementById('media_edit_name_input').value.trim();
    const theaterId = "{{ $theater->id ?? $theater->life_theater_id ?? '' }}";
    const renameBtn = document.getElementById('mediaRenameBtn');

    if (!newName) {
        alert('画像名を入力してください。');
        return;
    }

    renameBtn.disabled = true;

    try {
        const response = await fetch("{{ route('api.life_theater.media.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                id: currentEditMediaItem.id, // IDを指定してUPDATEさせる
                life_theater_id: theaterId,
                name: newName
            })
        });

        const result = await response.json();
        if (result.status === 'success') {
            switchMediaTab('list');
            loadMediaLibraryList();
        } else {
            alert(result.message || '名前の変更に失敗しました。');
        }
    } catch (e) {
        console.error(e);
        alert('エラーが発生しました。');
    } finally {
        renameBtn.disabled = false;
    }
}

// 選択モード用：画像の選択 (第3引数に mediaUrl を追加)
function selectMediaItem(mediaId, mediaName, mediaUrl = '') {
    if (targetMediaInputId) {
        const inputElem = document.getElementById(targetMediaInputId);
        if (inputElem) inputElem.value = mediaId;

        const labelElemId = targetMediaInputId.replace('_id', '_name');
        const labelElem = document.getElementById(labelElemId);
        if (labelElem) labelElem.textContent = mediaName;

        // ▼ プレビューサムネイルの更新処理 ▼
        const previewImg = document.getElementById(targetMediaInputId.replace('_id', '_preview'));
        const previewWrap = document.getElementById(targetMediaInputId.replace('_id', '_preview_wrap'));
        if (previewImg && previewWrap) {
            if (mediaUrl) {
                previewImg.src = mediaUrl;
                previewWrap.classList.remove('d-none');
            } else {
                previewWrap.classList.add('d-none');
            }
        }
    }
    closeModal('life_theater_media-modal');
}

function deleteMediaItem(mediaId) {
    openModal('common-modal', {
        title: '画像削除',
        mess: 'この画像を完全に削除しますか？\n※既に設定しているスライドがある場合、画像が表示されなくなります。',
        cancel_btn: 'キャンセル',
        confirm_btn: '削除',
        user_chk: true,
        onConfirm: function() {
            executeDeleteMediaItem(mediaId); // 確認OK時に実際のAjax削除を実行
        }
    });
}

// 実際の削除処理 (Ajax)
async function executeDeleteMediaItem(mediaId) {
    const requestUrl = "{{ route('api.life_theater.media.destroy', ['id' => '___ID___']) }}".replace('___ID___', mediaId);

    try {
        const response = await fetch(requestUrl, {
            method: 'DELETE',
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        const result = await response.json();

        if (result.status === 'success') {
            loadMediaLibraryList();
        } else {
            alert(result.message || '削除に失敗しました。');
        }
    } catch (e) {
        console.error(e);
        alert('削除処理中にエラーが発生しました。');
    }
}
function initCropperFromUrl(url) {
    destroyCropper();
    const image = document.getElementById('cropper_target_image');
    image.crossOrigin = 'anonymous';

    // ★ キャッシュによるCORSブロックを回避するため、タイムスタンプを付与してリクエスト
    const cacheBuster = (url.includes('?') ? '&' : '?') + 't=' + new Date().getTime();
    image.src = url + cacheBuster;

    document.getElementById('cropper_container').classList.remove('d-none');

    image.onload = function() {
        if (cropperInstance) cropperInstance.destroy();
        cropperInstance = new Cropper(image, {
            viewMode: 1,
            autoCropArea: 1,
            responsive: true,
            background: false
        });
    };
}

function initCropperFromFile(inputElem) {
    destroyCropper();
    if (!inputElem.files || !inputElem.files[0]) return;

    const file = inputElem.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        const image = document.getElementById('cropper_target_image');
        image.removeAttribute('crossOrigin');
        image.src = e.target.result;

        document.getElementById('cropper_container').classList.remove('d-none');

        cropperInstance = new Cropper(image, {
            viewMode: 1,
            autoCropArea: 1,
            responsive: true,
            background: false
        });
    };

    reader.readAsDataURL(file);
}

function destroyCropper() {
    if (cropperInstance) {
        cropperInstance.destroy();
        cropperInstance = null;
    }
    document.getElementById('cropper_container').classList.add('d-none');
}

// トリミング後にS3へ送信し、新規登録する処理 (Ajax)
async function uploadCroppedMediaFile() {
    const fileInput = document.getElementById('media_upload_file');
    const nameInput = document.getElementById('media_upload_name');
    const submitBtn = document.getElementById('mediaUploadSubmitBtn');
    const theaterId = "{{ $theater->id ?? $theater->life_theater_id ?? '' }}";
    const isManage = document.getElementById('media_manage_mode').value === '1';

    if (!cropperInstance && (!fileInput.files || fileInput.files.length === 0)) {
        alert('画像を選択してください。');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'アップロード中...';

    try {
        let uploadBlob = null;
        if (cropperInstance) {
            uploadBlob = await new Promise(resolve => {
                cropperInstance.getCroppedCanvas({
                    maxWidth: 1200,
                    maxHeight: 1200
                }).toBlob(blob => resolve(blob), 'image/jpeg', 0.85);
            });
        } else if (fileInput.files && fileInput.files[0]) {
            uploadBlob = fileInput.files[0];
        }

        if (!uploadBlob) throw new Error('画像の取得に失敗しました。');

        // 1. 署名付きURL発行
        const getUrlResponse = await fetch("{{ route('api.life_theater.media.presigned') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ life_theater_id: theaterId, filename: 'cropped_' + Date.now() + '.jpg' })
        });
        const urlResult = await getUrlResponse.json();
        if (urlResult.status !== 'success') throw new Error(urlResult.message || 'URL発行失敗');

        // 2. S3へPUT送信
        const uploadResponse = await fetch(urlResult.presigned_url, {
            method: 'PUT',
            headers: { 'Content-Type': 'image/jpeg' },
            body: uploadBlob
        });
        if (!uploadResponse.ok) throw new Error('アップロードに失敗しました。');

        // 3. DBへ新規作成（idは渡さないため新規登録）
        const saveName = currentEditMediaItem 
            ? (document.getElementById('media_edit_name_input').value || '切り抜き画像') 
            : (nameInput.value || '切り抜き画像');

        const saveResponse = await fetch("{{ route('api.life_theater.media.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                life_theater_id: theaterId,
                name: saveName,
                public_url: urlResult.public_url
            })
        });
        const saveResult = await saveResponse.json();

        if (saveResult.status === 'success' && saveResult.data) {
            document.getElementById('mediaUploadForm').reset();
            destroyCropper();
            currentEditMediaItem = null;

            if (isManage) {
                switchMediaTab('list');
                loadMediaLibraryList();
            } else {
                selectMediaItem(saveResult.data.id, saveResult.data.name || 'アップロード画像');
            }
        } else {
            alert(saveResult.message || '保存に失敗しました。');
        }
    } catch (e) {
        console.error(e);
        alert(e.message || 'エラーが発生しました。');
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = 'アップロード';
    }
}

// ==========================================
// 画像ライブラリ枠の拡張処理
// ==========================================
function confirmExpandMediaLimit() {
    // 既存の共通モーダル（common-modal）を使って確認ダイアログを出す
    openModal('common-modal', {
        title: '画像枠の拡張',
        mess: '100ポイントを使用して、画像の登録枠を「10枚」追加しますか？',
        cancel_btn: 'キャンセル',
        confirm_btn: '拡張する',
        user_chk: false,
        onConfirm: function() {
            executeExpandMediaLimit();
        }
    });
}

async function executeExpandMediaLimit() {
    const theaterId = "{{ $theater->id ?? $theater->life_theater_id ?? '' }}";
    if (!theaterId) return;

    try {
        const res = await $.ajax({
            type: "POST",
            url: "{{ route('api.life_theater.media.expand') }}", 
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            data: { life_theater_id: theaterId }
        });

        if (res.status === 'success') {
            if (typeof showNotification === 'function') {
                showNotification(res.message, "success", 2000);
            } else {
                alert(res.message);
            }
            // リスト（とバッジ・ボタン状態）を再読み込みして最新化
            loadMediaLibraryList();
        } else {
            alert(res.message || "拡張に失敗しました。");
        }
    } catch (err) {
        console.error(err);
        const errMsg = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : '処理中にエラーが発生しました。';
        alert(errMsg);
    }
}
</script>