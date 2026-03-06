@extends('layouts.app')

@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location='{{ route('remote.show') }}'"></i>
    <div class="container py-4">
        <div class="remote-header d-flex flex-column mb-3">
                
            <div class="title-text mx-auto w-100 overflow-hidden">
                <div class="d-grid align-items-center mb-2" style="grid-template-columns: 1fr auto 1fr; gap: 10px;">
                    <?//左側：空白?>
                    <div></div>
                    <?//中央：タイトル?>
                    <div class="text-center text-ellipsis">
                        <?//改行を禁止し、溢れた分は隠す（幅の制限は親のGridに従う） ?>
                        <h3 class="mb-0 text-nowrap text-truncate">{{ $iotdevice->type_name ?? '' }}: {{ $iotdevice->name ?? '' }}</h3>
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

                    {{-- 親デバイス hub_idがなければ親デバイスとする--}}
                    @if($iotdevice->hub_id == NULL)
                        <div class="child-devices text-center mb-3 text-ellipsis">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#childDevicesCollapse{{ $iotdevice->id }}" aria-expanded="false" aria-controls="childDevicesCollapse{{ $iotdevice->id }}">
                                子デバイス ({{ $iotdevice->child_devices->count() }})
                            </button>
                            <div class="collapse mt-2" id="childDevicesCollapse{{ $iotdevice->id }}">
                                <ul class="list-unstyled mb-0">
                                    @foreach($iotdevice->child_devices as $child)
                                        <li class="child-device">
                                            <small class="text-muted" style="cursor: pointer;" 
                                            onclick="window.location.href='{{ route('iotdevice.detail', ['id' => $child->id]) }}'">
                                                {{ $child->type_name ?? '' }}:{{ $child->name ?? '' }}<i class="fa-solid fa-gear"></i>
                                            </small>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    {{-- 子デバイス: 初期非表示 --}}
                    @else
                        <div class="parent-device text-center mb-3">
                            <small class="text-muted" style="cursor: pointer;" 
                                onclick="window.location.href='{{ route('iotdevice.detail', ['id' => $iotdevice->parent_device->id]) }}'">
                                {{ $iotdevice->parent_device->name }}<i class="fa-solid fa-gear"></i>
                            </small>
                        </div>
                    @endif

                </div>
                

                <div id="EditArea" class="mx-auto w-75 overflow-hidden" style="display: none;">
                    <?// 編集モード（最初は非表示）?>
                    <form id="iotdevicesNameChangeForm" method="POST" action="{{ route('iotdevice.change') }}">
                        @csrf
                        <input type="hidden" name="iotdevice_id" value="{{ $iotdevice->id ?? '' }}">
                        <input type="text" class="form-control form-control-sm me-2" name="iotdevice_name" value="{{ $iotdevice->name ?? '' }}" >
                    </form>
                    <form id="iotdevicesDeleteForm" method="POST" action="{{ route('iotdevice.delete') }}">
                        @csrf
                        <input type="hidden" name="iotdevice_id" value="{{ $iotdevice->id ?? '' }}">
                    </form>
                    
                    <?// 処理ボタンの表示?>
                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2">
                        <button type="button" class="btn btn-primary btn-sm"
                                onclick="openModal('common-modal',{
                                form_id: 'iotdevicesNameChangeForm',
                                title: 'デバイス名変更' ,mess: 'このデバイス名を変更しますか？',
                                cancel_btn: 'キャンセル',confirm_btn: '変更', user_chk: false,//チェック時にのみ実行可能
                            });">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm"
                                onclick="openModal('common-modal',{
                                form_id: 'iotdevicesDeleteForm',
                                title: 'デバイス削除' ,mess: 'このデバイス削除しますか？',
                                cancel_btn: 'キャンセル',confirm_btn: '削除', user_chk: true,//チェック時にのみ実行可能
                            });">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                        @php
                            $mess = '1\nXXXXXXXXXXXXXXXX、\nXXXXXXXXXXXXXXXX。';
                            $mess.= '\n※XXXXXXXXXXXXXXXX。';
                            $mess.= '\n2\nXXXXXXXXXXXXXXXX、\nXXXXXXXXXXXXXXXX。';
                        @endphp
                        <button type="button" class="btn btn-secondary btn-sm" 
                            onclick="openModal('common-modal', {
                                title: 'ヒント' ,mess:'{{ $mess }}',
                                user_chk: false
                            });">
                            <i class="fa-solid fa-circle-info"></i>
                        </button>
                    </div>


                
                    <?//デバイスごとの処理管理==============================================================================?>
                    @switch($iotdevice->type)
                        @case(0)
                            <p>赤外線リモコン</p>
                            @break

                        @case(1)
                            <span class="device-type"><i class="bi bi-lock"></i> スマートロック</span>
                            @break

                        @case(2)
                            <span class="device-type"><i class="bi bi-lightbulb"></i> 照明</span>
                            @break

                        @case(3)
                            <span class="device-type"><i class="bi bi-fan"></i> 扇風機</span>
                            @break

                        @case(4)
                            <span class="device-type"><i class="bi bi-plug"></i> コンセント</span>
                            @break

                        @case(5)
                            <span class="device-type"><i class="bi bi-thermometer-half"></i> 温度センサー</span>
                            @break

                        @case(6)
                            <span class="device-type"><i class="bi bi-wifi"></i> Wi-Fiデバイス</span>
                            @break

                        @default
                            <span class="device-type"><i class="bi bi-question-circle"></i> 未定義</span>
                    @endswitch

                    
                </div>
                <p class="detail-txt mb-0 text-center">
                    所有者：{{ $iotdevice->uname }}
                </p>
            </div>
        </div> 
    </div>


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
            // 「設定/完了」ボタンクリック
            toggleEditModeBtn.addEventListener('click', function() {
                if (isEditingMode) { // 現在が編集モード -> 「完了」が押されたと判断
                    // フォームを表示モードに戻す（ページリロードされるので、これは見た目上の切り替え）
                    setEditMode(false); 
                    // 実際のリダイレクトはサーバー側で行われるため、ここではUIを戻すだけ
                } else { // 現在が表示モード -> 編集モードに切り替え
                    setEditMode(true);
                }
            });

            // 初期状態は表示モード
            setEditMode(false);
        //===================================================================

    });
</script>