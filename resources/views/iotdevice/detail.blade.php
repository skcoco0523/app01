@extends('layouts.app')

@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location.href = '{{ route('remote.index') }}'"></i>
    <div class="container py-4">
        <div class="remote-header d-flex flex-column align-items-end mb-3">
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
                
                <p class="detail-txt mb-0 text-center">
                    所有者：{{ $iotdevice->uname }}
                </p>
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
                                            onclick="window.location.href='{{ route('iotdevice.show', ['id' => $child->id]) }}'">
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
                                onclick="window.location.href='{{ route('iotdevice.show', ['id' => $iotdevice->parent_device->id]) }}'">
                                {{ $iotdevice->parent_device->name }}<i class="fa-solid fa-gear"></i>
                            </small>
                        </div>
                    @endif

                </div>
                

                <div id="EditArea" class="mx-auto w-75 overflow-hidden" style="display: none;">
                    <?// 編集モード（最初は非表示）?>
                    <?// 処理ボタンの表示?>
                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2">
                        <button type="button" class="btn btn-primary btn-sm"
                                onclick="openModal('common-modal',{
                                form_id: 'iotdevicesNameUpdateForm',
                                title: 'デバイス名変更' ,mess: 'このデバイス情報を変更しますか？',
                                cancel_btn: 'キャンセル',confirm_btn: '変更', user_chk: false,//チェック時にのみ実行可能
                            });">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm"
                                onclick="openModal('common-modal',{
                                form_id: 'iotdevicesDestroyForm',
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

                    <form id="iotdevicesNameUpdateForm" method="POST" action="{{ route('iotdevice.update') }}">
                        @csrf
                        <input type="hidden" name="iotdevice_id" value="{{ $iotdevice->id ?? '' }}">
                        
                        <div class="mb-3">
                            <label for="iotdevice_name" class="form-label small fw-bold text-muted mb-1">デバイス名</label>
                            <input type="text" class="form-control form-control-sm" id="iotdevice_name" name="iotdevice_name" value="{{ $iotdevice->name ?? '' }}" placeholder="デバイス名を入力">
                        </div>

                        <?//マイク感度?>
                        @if($iotdevice->mic_flag)
                            <label for="mic_sensitivity" class="form-label small fw-bold text-muted mb-1">マイク感度</label>
                            <div class="mb-3 p-2 bg-white rounded border">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                </div>
                                <input type="range" class="form-range" id="mic_sensitivity" name="mic_sensitivity" 
                                    min="1" max="100" value="{{ $iotdevice->mic_sensitivity ?? 70 }}" 
                                    oninput="document.getElementById('mic_sensitivity_val').innerText = this.value">
                                <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                                    <span>低 (鈍感)</span>
                                    <span class="badge bg-primary text-white font-monospace">
                                        <span id="mic_sensitivity_val">{{ $iotdevice->mic_sensitivity ?? 70 }}</span>
                                    </span>
                                    <span>高 (敏感)</span>
                                </div>
                            </div>
                        @endif
                    </form>
                    <form id="iotdevicesDestroyForm" method="POST" action="{{ route('iotdevice.destroy') }}">
                        @csrf
                        <input type="hidden" name="iotdevice_id" value="{{ $iotdevice->id ?? '' }}">
                    </form>
                    

                
                    <?//デバイスごとの処理管理==============================================================================?>
                    @switch($iotdevice->type)
                        @case(0)
                            @break

                        @case(1)
                            @break

                        @case(2)
                            @break

                        @case(3)
                            @break

                        @case(4)
                            @break

                        @case(5)
                            @break

                        @case(6)
                            @break

                        @default
                    @endswitch

                    
                </div>
            </div>
        </div> 
    </div>


    <?//広告モーダル?>   
    @include('layouts.adv_popup')
@endsection

<script>
    document.addEventListener('DOMContentLoaded', function () {
        //===================================================================
        //モード切り替え関数 
        //===================================================================
            const DisplayArea = document.getElementById('DisplayArea');
            const EditArea = document.getElementById('EditArea');
            const toggleEditModeBtn = document.getElementById('toggleEditModeBtn');
            const buttonTextSpan = document.getElementById('buttonText');

            let isEditingMode = false;

            function setEditMode(enableEdit) {
                isEditingMode = enableEdit;
                if (isEditingMode) {
                    DisplayArea.style.display = 'none';
                    EditArea.style.display = 'block';
                    buttonTextSpan.textContent = '閉じる';
                } else {
                    DisplayArea.style.display = 'block';
                    EditArea.style.display = 'none';
                    buttonTextSpan.textContent = '設定';
                }
            }

            toggleEditModeBtn.addEventListener('click', function() {
                setEditMode(!isEditingMode);
            });

            setEditMode(false);
        //===================================================================

    });
</script>
