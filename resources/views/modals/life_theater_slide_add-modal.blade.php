<div id="life_theater_slide_add-modal" class="notification-overlay" onclick="closeModal('life_theater_slide_add-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-content">
            <form action="{{ route('life_theater.slide.store') }}" method="POST">
                @csrf
                <input type="hidden" name="life_theater_id" value="{{ $theater->id ?? $theater->life_theater_id }}">
                <input type="hidden" name="life_theater_media_id" id="add_slide_media_id" value="">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle"></i> コマ（スライド）の追加</h5>
                    <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('life_theater_slide_add-modal')"></button>
                </div>
                <div class="modal-body">
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

                        {{-- ▼ サムネイルプレビュー表示部 ▼ --}}
                        <div id="add_slide_media_preview_wrap" class="d-none border rounded p-1 text-center bg-light" style="width: 100px;">
                            <img id="add_slide_media_preview" src="" class="img-fluid rounded" style="height: 70px; object-fit: cover; width: 100%;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer row gap-3 justify-content-center">
                    <button type="button" class="col-5 btn btn-secondary" onclick="closeModal('life_theater_slide_add-modal')">キャンセル</button>
                    <button id="slide_add_confirm_button" type="submit" class="col-5 btn btn-danger disabled">追加</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var slideTitleInput = document.getElementById('slide_add_title');
    var confirmButton = document.getElementById('slide_add_confirm_button');

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
});
</script>