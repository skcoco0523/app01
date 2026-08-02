@extends('layouts.app')

@section('content')
    <script src="{{ asset('js/smart-remote.js') }}"></script>
    <style>
        /* 編集モード時は未割当状態を変えない（はっきり表示する） */
        #RemoteDesignContainer.is-edit-mode .noset-signal {
            opacity: 1 !important;
        }
    </style>
    <i class="fa-solid fa-angles-left" onclick="window.location='{{ route('remote.index') }}'"></i>
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
                <div id="DisplayArea" class="mx-auto w-75 overflow-hidden">
                    
                </div>
                <?// 編集モード（最初は非表示）?>
                <div id="EditArea" class="mx-auto w-75 overflow-hidden" style="display: none;">
                        
                    <?// 編集フォーム?>
                    <form id="remoteNameUpdateForm" method="POST" action="{{ route('remote.update') }}">
                        @csrf
                        <?// 変更権限がある場合のみ入力許可?>
                        <input type="hidden" id="remote_id" name="remote_id" value="{{ $virtual_remote->remote_id ?? '' }}">
                        <input type="hidden" id="remote_user_id" name="remote_user_id" value="{{ $virtual_remote->id ?? '' }}">

                        @if($virtual_remote->admin_flag ?? false)
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" name="remote_name" value="{{ $virtual_remote->name ?? '' }}" >
                            </div>
                            
                            <?// ライブラリ型リモコンの場合のみ送信デバイス選択を表示?>
                            <div id="LibraryDeviceSelectArea" style="display: none;" class="mb-3">
                                <select name="device_id" id="library_device_select" class="form-select form-select-sm">
                                    <option value="0">送信先を選択してください</option>
                                </select>
                            </div>
                        @else
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" value="{{ $virtual_remote->name ?? '' }}" disabled>
                            </div>
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm" value="{{ $virtual_remote->device_name ?? '' }}" disabled>
                            </div>

                        @endif

                        @if($virtual_remote->admin_flag ?? false)
                        @endif
                    </form>

                    <?// 削除フォーム?>
                    <form id="remoteDestroyForm" method="POST" action="{{ route('remote.destroy') }}">
                        @csrf
                        <input type="hidden" name="remote_id" value="{{ $virtual_remote->remote_id ?? '' }}">
                        <input type="hidden" name="remote_user_id" value="{{ $virtual_remote->id ?? '' }}">
                    </form>

                    <?// 共有解除フォーム?>
                    <form id="remoteUnShareForm" method="POST" action="{{ route('remote.unshare') }}">
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
                                    form_id: 'remoteNameUpdateForm',title: 'リモコン名変更' ,mess: 'このリモコン名を変更しますか？',
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
                                    form_id: 'remoteDestroyForm',title: 'リモコン削除' ,mess: 'このリモコンを削除しますか？',
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
        <div id="RemoteDesignContainer">
            @include($virtual_remote->blade_path)
        </div>
    </div>

    <?//広告モーダル?>   
    @include('layouts.adv_popup')
        
    <!-- リモコンボタン設定モーダル -->
    @include('modals.edit_virtualremote_signal-modal')

@endsection

<script>
    document.addEventListener('DOMContentLoaded', function () {
        //===================================================================
        // モード切り替え関数
        //===================================================================
        const DisplayArea = document.getElementById('DisplayArea');
        const EditArea = document.getElementById('EditArea');
        const toggleEditModeBtn = document.getElementById('toggleEditModeBtn');
        const buttonTextSpan = document.getElementById('buttonText');

        // 共通インスタンスの初期化
        const remoteId = document.getElementById('remote_id')?.value || '';
        window.smartRemoteInstance = new SmartRemote(
            remoteId,
            '{{ csrf_token() }}',
            irSendSignalUrl
        );

        function setEditMode(enableEdit) {
            window.smartRemoteInstance.setEditMode(enableEdit);
            const designContainer = document.getElementById('RemoteDesignContainer');
            if (enableEdit) { // 編集モードに入る
                DisplayArea.style.display = 'none';
                EditArea.style.display = 'block';
                buttonTextSpan.textContent = '閉じる';
                designContainer.classList.add('is-edit-mode');

                // ライブラリ型ボタンが存在する場合、デバイス選択を表示
                if (document.querySelectorAll('button[data-lib-protocol]').length > 0) {
                    initLibraryDeviceSelect();
                }

            } else { // 表示モードに戻る
                DisplayArea.style.display = 'block';
                EditArea.style.display = 'none';
                buttonTextSpan.textContent = '設定';
                designContainer.classList.remove('is-edit-mode');
            }
        }

        toggleEditModeBtn.addEventListener('click', function() {
            setEditMode(!window.smartRemoteInstance.isEditingMode);
        });

        setEditMode(false);
        
        // ----------------------------------------------------------
        // ボタンのクリック処理 (RAW信号/編集)
        // ----------------------------------------------------------
        document.querySelectorAll('button[data-button-num]').forEach(btn => {
            btn.addEventListener('click', function() {
                const buttonNum = btn.dataset.buttonNum;
                const buttonName = btn.dataset.buttonName;

                if (window.smartRemoteInstance.isEditingMode) {
                    openModal('edit_virtualremote_signal-modal', {
                        remote_id: remoteId,
                        button_num: buttonNum,
                        button_name: buttonName,
                    });
                } else {
                    window.smartRemoteInstance.sendSignal(buttonNum);
                }
            });
        });

        // ----------------------------------------------------------
        // ライブラリ送信用 (data-lib-protocol) 固定値ボタン用
        // アクションがないボタンのみ、共通処理で送信する
        // ----------------------------------------------------------
        document.querySelectorAll('button[data-lib-protocol]:not([data-action])').forEach(btn => {
            btn.addEventListener('click', function() {
                if (window.smartRemoteInstance.isEditingMode) return;

                const protocol = btn.dataset.libProtocol;
                const options = {};
                if (btn.dataset.libTemp !== undefined)   options.temp  = btn.dataset.libTemp;
                if (btn.dataset.libMode !== undefined)   options.mode  = btn.dataset.libMode;
                if (btn.dataset.libFan !== undefined)    options.fan   = btn.dataset.libFan;
                if (btn.dataset.libPower !== undefined)  options.power = btn.dataset.libPower;
                
                const hex      = btn.dataset.libHex;
                const bits     = btn.dataset.libBits;
                
                window.smartRemoteInstance.sendLibrary(protocol, hex, bits, options);
            });
        });

        // ----------------------------------------------------------
        // ライブラリ型デバイス選択の初期化
        // ----------------------------------------------------------
        async function initLibraryDeviceSelect() {
            const area = document.getElementById('LibraryDeviceSelectArea');
            const select = document.getElementById('library_device_select');
            const currentDeviceId = '{{ $virtual_remote->device_id ?? 0 }}';

            if (!area || !select) return;
            area.style.display = 'block';

            // 既にロード済みの場合はスキップ（または最新化したい場合はリロード）
            if (select.options.length > 1) return;

            try {
                const deviceList = await get_iot_device();
                if (deviceList && deviceList.length > 0) {
                    deviceList.forEach(device => {
                        const option = document.createElement('option');
                        option.value = device.id;
                        option.textContent = device.name;
                        if (device.id == currentDeviceId) option.selected = true;
                        select.appendChild(option);
                    });
                }
            } catch (err) {
                console.error('Failed to fetch devices:', err);
            }
        }

        // 登録済みデバイス取得 (ご提示いただいた関数)
        async function get_iot_device() {
            return new Promise((resolve, reject) => {
                $.ajax({
                    type: "get",
                    url: getIotDevicesUrl,
                    data: {},
                })
                .done(data => {
                    if (data && data.length > 0)    resolve(data);
                    else                            resolve([]);
                })
                .fail((xhr, status, error) => {
                    console.error('Error fetching devices:', error);
                    reject(error);
                });
            });
        }

            

    });
</script>
