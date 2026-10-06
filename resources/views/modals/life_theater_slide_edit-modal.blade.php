<div id="life_theater_slide_edit-modal" class="notification-overlay" onclick="closeModal('life_theater_slide_edit-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-content">
            <form action="{{ route('life_theater.slide.update') }}" method="POST">
                @csrf
                <input type="hidden" name="id" id="edit_slide_id" value="">
                <input type="hidden" name="life_theater_media_id" id="edit_slide_media_id" value="">
                
                {{-- ▼ 追加: modal.jsから画像URLを受け取る用の隠しフィールド ▼ --}}
                <input type="hidden" id="edit_slide_media_url" value="">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square"></i> コマ（スライド）の編集</h5>
                    <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('life_theater_slide_edit-modal')"></button>
                </div>
                <div class="modal-body">
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

                        {{-- サムネイルプレビュー表示部 --}}
                        <div id="edit_slide_media_preview_wrap" class="d-none border rounded p-1 text-center bg-light" style="width: 100px;">
                            <img id="edit_slide_media_preview" src="" class="img-fluid rounded" style="height: 70px; object-fit: cover; width: 100%;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer row gap-3 justify-content-center">
                    <button type="button" class="col-5 btn btn-secondary" onclick="closeModal('life_theater_slide_edit-modal')">キャンセル</button>
                    <button type="submit" class="col-5 btn btn-primary">更新</button>
                </div>
            </form>
            <div class="mb-3 border-top pt-3">
                <label class="form-label fw-bold d-block mb-1">
                    <i class="fa-solid fa-comments me-1"></i> キャスト・吹き出し設定
                </label>
                <div class="d-flex align-items-center justify-content-between bg-light p-2 border rounded">
                    <small class="text-muted">スライド内の人物や吹き出しセリフを追加・編集します</small>
                    <button type="button" class="btn btn-outline-primary btn-sm"
                        onclick="
                            const slideId = document.getElementById('edit_slide_id').value;
                            openModal('life_theater_slide_object-modal', {
                                object_target_slide_id: slideId
                            });
                        ">
                        <i class="fa-solid fa-pen-to-square me-1"></i> キャスト・吹き出しの編集
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('life_theater_slide_edit-modal');
    if (editModal) {
        editModal.addEventListener('modal:open', function () {
            // モーダルオープン時、隠しフィールドのURLを取得してプレビューに反映
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
        });
    }
});
</script>