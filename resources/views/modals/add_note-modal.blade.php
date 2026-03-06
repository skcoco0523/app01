

<div id="add_note-modal" class="notification-overlay" onclick="closeModal('add_note-modal')">
    <div class="notification-modal" onclick="event.stopPropagation()">
        <div class="modal-content">
            <?// 新規メモ登録フォーム ?>
            <form action="{{ route('note.create') }}" method="POST">
                @csrf
                <input type="hidden" name="check_flag" value="false" >
                <div class="modal-header">
                    <h5 class="modal-title" id="newIotDeviceModalLabel">新規メモ登録</h5>
                    <button type="button" class="btn-close" aria-label="Close" onclick="closeModal('add_note-modal')"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="note_title" class="form-label">タイトル</label>
                        <input type="text" class="form-control" id="note_title" name="title" placeholder="メモ名を入力" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">背景色</label>
                        <?// 横スクロールを有効にするための設定 ?>
                        <div id="color-palette" 
                            class="d-flex flex-nowrap gap-3 p-2 border rounded bg-light" 
                            style="overflow-x: auto; -webkit-overflow-scrolling: touch; scrollbar-width: none;">
                            
                            @foreach(config('common.note_colors') as $key => $color)
                                <div class="color-circle flex-shrink-0" data-value="{{ $key }}" data-code="{{ $color['code'] }}"
                                    style="background-color: {{ $color['code'] }}; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; border: 2px solid #fff; box-shadow: 0 0 4px rgba(0,0,0,0.2); transition: transform 0.2s;">
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="color_num" id="color_num" value="">
                    </div>

                    <div class="mb-3">
                        <label for="note_content" class="form-label">内容</label>
                        <textarea class="form-control" id="note_content" name="content" rows="5" placeholder="メモ内容を入力" required></textarea>
                    </div>
                    
                </div>
                <div class="modal-footer row gap-3 justify-content-center">

                    <button type="button" class="col-5 btn btn-secondary" onclick="closeModal('add_note-modal')">キャンセル</button>
                    <button id="note_add_confirm_button" type="submit" class="col-5 btn btn-danger disabled">保存</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    
document.addEventListener('DOMContentLoaded', function() {
    var noteTitleInput = document.getElementById('note_title');
    var noteContentInput = document.getElementById('note_content');
    var confirmButton = document.getElementById('note_add_confirm_button');
    function checkInput() {
        // 入力がある場合は保存ボタンを有効にする
        //if (noteTitleInput.value.trim() !== '' && noteContentInput.value.trim() !== '') 
        if (noteTitleInput.value.trim() !== '') 
            confirmButton.classList.remove('disabled');
        else
            confirmButton.classList.add('disabled');
    }
    // 入力が変更されたときにチェックする
    noteTitleInput.addEventListener('input', checkInput);
    noteContentInput.addEventListener('input', checkInput);
    // 初期状態をチェックする
    checkInput();

    document.getElementById('color_num').addEventListener('change', function() {
        // 選択されたoptionからdata-code（16進数カラーコード）を取得
        const selectedOption = this.options[this.selectedIndex];
        const colorCode = selectedOption.getAttribute('data-code');
        
        // プレビューボックスの背景色を更新
        document.getElementById('color_num').style.backgroundColor = colorCode;
    });

            
    //メモカラー選択処理
    const colorInput = document.getElementById('color_num');

    function selectColor(val) {
        document.querySelectorAll('.color-circle').forEach(c => {
            const isTarget = c.getAttribute('data-value') == val;
            c.style.borderColor = isTarget ? '#000' : '#fff';
            c.style.transform = isTarget ? 'scale(1.1)' : 'scale(1)';
        });
        colorInput.value = val;
    }
    document.querySelectorAll('.color-circle').forEach(circle => {
        circle.addEventListener('click', function() { selectColor(this.getAttribute('data-value'));});
    });

    //初期値は0
    selectColor(0);



});
</script>