@extends('layouts.app')

@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location='{{ route('remote-show') }}'"></i>
    <div class="container py-4">
        <div class="remote-header d-flex flex-column align-items-end mb-3">
            
            <div class="title-text mx-auto w-100 overflow-hidden">
                <div class="d-grid align-items-center mb-2" style="grid-template-columns: 1fr auto 1fr; gap: 10px;">
                    <?//左側：空白?>
                    <div></div>
                    <?//中央：タイトル?>
                    <div class="text-center text-ellipsis">
                        <?//改行を禁止し、溢れた分は隠す（幅の制限は親のGridに従う） ?>
                        <h3 class="mb-0 text-nowrap text-truncate">{{ $virtual_remote->name ?? '' }}</h3>
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
                    
                </div>
                <?// 編集モード（最初は非表示）?>
                <div id="EditArea" style="display: none;">
                        
                    <?// 編集フォーム?>
                    <form id="remoteNameChangeForm" method="POST" action="{{ route('remote-chg') }}">
                        @csrf
                        <?// 変更権限がある場合のみ入力許可?>
                        <input type="hidden" name="remote_id" value="{{ $virtual_remote->remote_id ?? '' }}">
                        @if($virtual_remote->admin_flag ?? false)
                            <input type="text" class="form-control form-control-sm me-2" name="remote_name" value="{{ $virtual_remote->name ?? '' }}" >
                        @else
                            <input type="text" class="form-control form-control-sm me-2" value="{{ $virtual_remote->name ?? '' }}" disabled>
                        @endif
                    </form>

                    <?// 削除フォーム?>
                    <form id="remoteDeleteForm" method="POST" action="{{ route('remote-del') }}">
                        @csrf
                        <input type="hidden" name="remote_id" value="{{ $virtual_remote->remote_id ?? '' }}">
                        <input type="hidden" name="remote_user_id" value="{{ $virtual_remote->id ?? '' }}">
                    </form>

                    <?// 共有解除フォーム?>
                    <form id="remoteUnShareForm" method="POST" action="{{ route('remote-unshare') }}">
                        @csrf
                        <input type="hidden" name="remote_id" value="{{ $virtual_remote->remote_id ?? '' }}">
                        <input type="hidden" name="remote_user_id" value="{{ $virtual_remote->id ?? '' }}">
                    </form>
                    <?// 処理可能ボタンの表示?>
                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2">
                        <?// 変更ボタン?>
                        @if($virtual_remote->admin_flag ?? false)
                            <button type="button" class="btn btn-primary btn-sm"
                                onclick="openModal('common-modal',{
                                    form_id: 'remoteNameChangeForm',title: 'リモコン名変更' ,mess: 'このリモコン名を変更しますか？',
                                    cancel_btn: 'キャンセル',confirm_btn: '変更', user_chk: false,//チェック不要
                                });">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        @endif

                        <?// 削除・共有解除ボタン?>
                        @if(($virtual_remote->admin_user_id ?? 0) == Auth::id())
                            <?// 所有者のみ削除可能?>
                            <button type="button" class="btn btn-danger btn-sm" 
                                onclick="openModal('common-modal',{
                                    form_id: 'remoteDeleteForm',title: 'リモコン削除' ,mess: 'このリモコンを削除しますか？',
                                    cancel_btn: 'キャンセル',confirm_btn: '削除', user_chk: true,//チェック時にのみ実行可能
                                });">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        @else
                            <?// 所有者でない場合は共有解除?>
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="openModal('common-modal',{
                                    form_id: 'remoteUnShareForm',title: 'リモコンの共有解除' ,mess: 'このリモコンの共有を解除しますか？',
                                    cancel_btn: 'キャンセル',confirm_btn: '解除', user_chk: true,//チェック時にのみ実行可能
                                });">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        @endif

                        <?// ヘルプ表示ボタン?>
                        @if($virtual_remote->admin_flag ?? false)
                            @php
                                $mess = '1\n仮想リモコンのボタンを選択し、\n受信するデバイスを選択してください。';
                                $mess.= '\n※デバイスの事前登録が必要です。';
                                $mess.= '\n2\nデバイスが受信待機状態になったら、\n実物のリモコンのボタンを押してください。';
                                $mess.= '\n3\n受信成功後、\n仮想リモコンへの登録が可能です。';
                            @endphp
                            <button type="button" class="btn btn-secondary btn-sm" 
                                onclick="openModal('common-modal', {
                                    title: 'ヒント' ,mess:'{{ $mess }}',
                                    user_chk: false
                                });">
                                <i class="fa-solid fa-circle-info"></i>
                            </button>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <?//リモコンデザイン?>
        @include($virtual_remote->blade_path)
    </div>

    <?//広告モーダル?>   
    @include('layouts.adv_popup')
        
    <!-- リモコンボタン設定モーダル -->
    @include('modals.edit_virtualremote_signal-modal')

@endsection

<script>
    document.addEventListener('DOMContentLoaded', function () {
        //===================================================================
        //モード切り替え関数 ☆☆☆
        //===================================================================
            const DisplayArea = document.getElementById('DisplayArea');
            const EditArea = document.getElementById('EditArea');
            //const remoteNameInput = document.getElementById('remoteNameInput');
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
            
            // ----------------------------------------------------------
            // data-button-num を持つボタンのクリック処理
            // ----------------------------------------------------------
            const remoteId = document.getElementById('remote_id')?.value || '';
            document.querySelectorAll('button[data-button-num]').forEach(btn => {
                btn.addEventListener('click', function() {
                    const buttonNum = btn.dataset.buttonNum;
                    const buttonName = btn.dataset.buttonName;

                    if (isEditingMode) {    // 編集モードの場合の処理
                        console.log(`編集モード: ボタン ${buttonNum} : ${buttonName} を編集`);
                        openModal('edit_virtualremote_signal-modal', {
                            remote_id: remoteId,
                            button_name: buttonName,
                        });

                    } else {    // 通常モード（送信モード）の処理
                        sendRemoteSignal(remoteId, buttonNum);
                    }
                });
            });

            function sendRemoteSignal(remoteId, buttonNum) {
                console.log(`sendRemoteSignal()   remoteId: ${remoteId}  buttonNum: ${buttonNum} `);
                // Ajaxやフォーム送信など
            }
            

    });
</script>