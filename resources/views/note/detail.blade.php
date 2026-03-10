@extends('layouts.app')

@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location='{{ route('note.index') }}'"></i>
    <div class="container py-4">
        <div class="note-header d-flex flex-column mb-2">
            
            <div class="title-text mx-auto w-100 overflow-hidden">
                <div class="d-grid align-items-center mb-2" style="grid-template-columns: 1fr auto 1fr; gap: 10px;">
                    <?//左側：空白?>
                    <div></div>
                    <?//中央：タイトル?>
                    <div class="text-center text-ellipsis">
                        <?//改行を禁止し、溢れた分は隠す（幅の制限は親のGridに従う） ?>
                        <h3 class="mb-0 text-nowrap text-truncate">{{ $note->title ?? '' }}</h3>
                    </div>
                    <?//右側：設定ボタン?>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary btn-sm text-nowrap" id="toggleEditModeBtn">
                            <i class="fa-solid fa-gear"></i> <span id="buttonText">設定</span>
                        </button>
                    </div>
                </div>

                <?//表示モード?>
                <div id="DisplayArea">
                    <div class="mb-3" style="background-color: {{ $note->color_code ?? '#fff' }}; min-height: 100vh;">
                        {!! nl2br(e($note->content ?? '')) !!}
                    </div>
                </div>
                <?// 編集モード（最初は非表示）?>
                <div id="EditArea" style="display: none;">
                    <?// 処理可能ボタンの表示?>
                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2 mb-3">
                        <?// 変更ボタン?>
                        @if(($note->owner_flag || $note->admin_flag ?? false) && $note->edit_lock_flag == false)
                            <button type="button" class="btn btn-primary btn-sm"
                                onclick="openModal('common-modal',{
                                    form_id: 'noteUpdateForm',title: 'メモ変更' ,mess: 'このメモを変更しますか？',
                                    cancel_btn: 'キャンセル',confirm_btn: '変更', user_chk: false,//チェック不要
                                });">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        @endif
                        <?// 共有ボタン?>
                        @if($note->owner_flag)
                            <button type="button" class="btn btn-primary btn-sm"
                                onclick="openModal('share_note-modal',{ note_id: '{{ $note->id ?? '' }}', note_title: '{{ $note->title ?? '' }}'});">
                                <i class="fa-solid fa-user-plus"></i>
                            </button>
                        @endif
                        <?// ロック解除ボタン（現在ロックされている場合に表示）?>
                        @if($note->owner_flag && $note->edit_lock_flag == true)
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="openModal('common-modal',{
                                    form_id: 'noteUnlockForm', title: 'ロック解除' ,mess: 'ロックを解除して編集を許可しますか？',
                                    cancel_btn: 'キャンセル', confirm_btn: '解除する', user_chk: false,
                                });">
                                <i class="fa-solid fa-lock-open"></i>
                            </button>
                        @endif

                        <?// ロックボタン（現在はロックされていない場合に表示）?>
                        @if($note->owner_flag && $note->edit_lock_flag == false)
                            <button type="button" class="btn btn-outline-danger btn-sm"
                                onclick="openModal('common-modal',{
                                    form_id: 'noteLockForm', title: '編集ロック' ,mess: 'このメモをロックして編集できないようにしますか？',
                                    cancel_btn: 'キャンセル', confirm_btn: 'ロックする', user_chk: false,
                                });">
                                <i class="fa-solid fa-lock"></i>
                            </button>
                        @endif

                        <?// 削除・共有解除ボタン?>
                        @if($note->owner_flag)
                            <?// 所有者のみ削除可能?>
                            <button type="button" class="btn btn-danger btn-sm" 
                                onclick="openModal('common-modal',{
                                    form_id: 'noteDestroyForm',title: 'メモ削除' ,mess: 'このメモを削除しますか？',
                                    cancel_btn: 'キャンセル',confirm_btn: '削除', user_chk: true,//チェック時にのみ実行可能
                                });">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        @else
                            <?// 所有者でない場合は共有解除?>
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="openModal('common-modal',{
                                    form_id: 'noteUnShareForm',title: 'メモの共有解除' ,mess: 'このメモの共有を解除しますか？',
                                    cancel_btn: 'キャンセル',confirm_btn: '解除', user_chk: true,//チェック時にのみ実行可能
                                });">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        @endif

                    </div>

                    <?// 編集フォーム?>
                    <form id="noteUpdateForm" method="POST" action="{{ route('note.update') }}">
                        @csrf
                        <?// 変更権限がある場合のみ入力許可?>
                        <input type="hidden" name="id" value="{{ $note->id ?? '' }}">
                        <input type="hidden" name="share_flag" value="{{ $input['share_flag'] ?? '' }}">
                        @if(($note->owner_flag || $note->admin_flag ?? false) && $note->edit_lock_flag == false)
                            <div class="mb-3">
                                <input type="text" class="form-control form-control-sm me-2" name="title" value="{{ $note->title ?? '' }}" >
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
                                <input type="hidden" name="color_num" id="color_num" value="{{ $note->color_num ?? '' }}">
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" id="note_content" name="content" rows="20" placeholder="メモ内容を入力" required>{{ $note->content ?? '' }}</textarea>
                            </div>
                        @else
                            <input type="text" class="form-control form-control-sm me-2" value="{{ $note->title ?? '' }}" disabled>
                        @endif
                    </form>

                    <?// 削除フォーム?>
                    <form id="noteDestroyForm" method="POST" action="{{ route('note.destroy') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $note->id ?? '' }}">
                    </form>

                    <?// 共有解除フォーム?>
                    <form id="noteUnShareForm" method="POST" action="{{ route('note.unshare') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $note->id ?? '' }}">
                    </form>

                    <?// ロックフォーム?>
                    <form id="noteLockForm" method="POST" action="{{ route('note.update') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $note->id ?? '' }}">
                        <input type="hidden" name="edit_lock_flag" value=1>
                    </form>
                    <?// ロック解除フォーム?>
                    <form id="noteUnlockForm" method="POST" action="{{ route('note.update') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $note->id ?? '' }}">
                        <input type="hidden" name="edit_lock_flag" value=0>
                    </form>


                </div>
            </div>
        </div>

    </div>


    <!-- 共有メモポップアップモーダル -->
    @include('modals.share_note-modal')

    <?//広告モーダル?>   
    @include('layouts.adv_popup')

@endsection

<script>
    document.addEventListener('DOMContentLoaded', function () {
    //===================================================================
    //モード切り替え関数 ☆☆☆
    //===================================================================
        const DisplayArea = document.getElementById('DisplayArea');
        const EditArea = document.getElementById('EditArea');
        const noteColorSelect = document.getElementById('color_num');
        //const noteNameInput = document.getElementById('noteNameInput');
        const toggleEditModeBtn = document.getElementById('toggleEditModeBtn');
        const buttonTextSpan = document.getElementById('buttonText');
        const buttonIcon = toggleEditModeBtn.querySelector('i');

        let isEditingMode = false; // 現在のモード状態を保持

        function setEditMode(enableEdit) {
            isEditingMode = enableEdit;
            if (isEditingMode) { // 編集モードに入る
                DisplayArea.style.display = 'none';
                EditArea.style.display = 'block';
                buttonTextSpan.textContent = '閉じる';
            } else { // 表示モードに戻る
                DisplayArea.style.display = 'block';
                EditArea.style.display = 'none';
                buttonTextSpan.textContent = '設定';
            }
        }
        // 設定の切り替え
        toggleEditModeBtn.addEventListener('click', function() {
            if (isEditingMode)  setEditMode(false); 
            else                setEditMode(true);
        });

        // 初期状態は表示モード
        setEditMode(false);
        //===================================================================
            
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

        if (colorInput.value !== "") selectColor(colorInput.value);

    });
</script>