<div id="life_theater_add-modal" class="notification-overlay" onclick="closeModal('life_theater_add-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-content">
            <form action="{{ route('life_theater.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="newLifeTheaterModalLabel">新規作品登録</h5>
                    <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('life_theater_add-modal')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="life_theater_title" class="form-label">タイトル <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="life_theater_title" name="title" placeholder="作品名を入力" required>
                    </div>

                    <div class="mb-3">
                        <label for="life_theater_subtitle" class="form-label">サブタイトル</label>
                        <input type="text" class="form-control" id="life_theater_subtitle" name="subtitle" placeholder="サブタイトルを入力">
                    </div>

                    <div class="mb-3">
                        <label for="life_theater_bgm" class="form-label">BGM</label>
                        <select class="form-select" id="life_theater_bgm" name="bgm_type">
                            <option value="Tiny_Steps.mp3" selected>Tiny Steps</option>
                            <option value="Happy_Day.mp3">Happy Day</option>
                            <option value="Gentle_Memories.mp3">Gentle Memories</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">背景色（テーマ）</label>
                        <div id="theater-color-palette" 
                            class="d-flex flex-nowrap gap-3 p-2 border rounded bg-light" 
                            style="overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none;">
                            
                            @foreach(config('common.note_colors', []) as $key => $color)
                                <div class="theater-color-circle flex-shrink-0" data-value="{{ $key }}" data-code="{{ $color['code'] }}"
                                    style="background-color: {{ $color['code'] }}; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; border: 2px solid #fff; box-shadow: 0 0 4px rgba(0,0,0,0.2); transition: transform 0.2s;">
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="theme_color_num" id="theater_theme_color_num" value="0">
                    </div>

                </div>
                <div class="modal-footer row gap-3 justify-content-center">
                    <button type="button" class="col-5 btn btn-secondary" onclick="closeModal('life_theater_add-modal')">キャンセル</button>
                    <button id="theater_add_confirm_button" type="submit" class="col-5 btn btn-danger disabled">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var theaterTitleInput = document.getElementById('life_theater_title');
    var confirmButton = document.getElementById('theater_add_confirm_button');

    function checkInput() {
        if (theaterTitleInput.value.trim() !== '') 
            confirmButton.classList.remove('disabled');
        else
            confirmButton.classList.add('disabled');
    }

    theaterTitleInput.addEventListener('input', checkInput);
    checkInput();

    // カラー選択処理
    const colorInput = document.getElementById('theater_theme_color_num');

    function selectColor(val) {
        document.querySelectorAll('.theater-color-circle').forEach(c => {
            const isTarget = c.getAttribute('data-value') == val;
            c.style.borderColor = isTarget ? '#000' : '#fff';
            c.style.transform = isTarget ? 'scale(1.1)' : 'scale(1)';
        });
        colorInput.value = val;
    }

    document.querySelectorAll('.theater-color-circle').forEach(circle => {
        circle.addEventListener('click', function() { 
            selectColor(this.getAttribute('data-value'));
        });
    });

    selectColor(0);
});
</script>