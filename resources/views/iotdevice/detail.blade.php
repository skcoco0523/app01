@extends('layouts.app')

@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location.href = '{{ route('remote.index') }}'"></i>
    <div class="container py-4">
        <div class="remote-header d-flex flex-column align-items-end mb-3">
            <div class="title-text mx-auto w-100 overflow-hidden">
                <div class="d-grid align-items-center mb-2" style="grid-template-columns: 1fr auto 1fr; gap: 10px;">
                    {{-- 左側：空白 --}}
                    <div></div>
                    {{-- 中央：タイトル --}}
                    <div class="text-center text-ellipsis">
                        <h3 class="mb-0 text-nowrap text-truncate">{{ $iotdevice->type_name ?? '' }}: {{ $iotdevice->name ?? '' }}</h3>
                    </div>
                    {{-- 右側：設定ボタン --}}
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary btn-sm text-nowrap" id="toggleEditModeBtn">
                            <i class="fa-solid fa-gear"></i> <span id="buttonText">設定</span>
                        </button>
                    </div>
                </div>
                
                <p class="detail-txt mb-2 text-center text-muted small">
                    所有者：{{ $iotdevice->uname }}
                </p>

                {{-- 表示モード --}}
                <div id="DisplayArea" class="mx-auto w-75 overflow-hidden">

                    {{-- 親デバイス/子デバイス関係の表示 --}}
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
                                                {{ $child->type_name ?? '' }}:{{ $child->name ?? '' }} <i class="fa-solid fa-gear"></i>
                                            </small>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @else
                        <div class="parent-device text-center mb-3">
                            <small class="text-muted" style="cursor: pointer;" 
                                onclick="window.location.href='{{ route('iotdevice.show', ['id' => $iotdevice->parent_device->id]) }}'">
                                {{ $iotdevice->parent_device->name }} <i class="fa-solid fa-gear"></i>
                            </small>
                        </div>
                    @endif

                    {{-- 現在の設定ステータス表示 --}}
                    <div class="card border-0 bg-light p-3 mb-3 rounded shadow-sm">
                        <div class="d-flex flex-column gap-2" style="font-size: 12px;">

                            {{-- ウェイクワード感度 --}}
                            @if($iotdevice->ww_flag)
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted"><i class="fa-solid fa-microphone me-1"></i>ウェイクワード感度</span>
                                    <span class="badge bg-dark font-monospace">{{ max(70, min(100, $iotdevice->mic_sensitivity ?? 70)) }}</span>
                                </div>
                            @endif

                            {{-- AI応答モード --}}
                            @if($iotdevice->ai_flag)
                                @php
                                    $mode = $iotdevice->ai_reply_mode ?? 'command';
                                    $modeLabels = [
                                        'command' => ['label' => '操作のみ (+0pt)', 'class' => 'bg-secondary'],
                                        'voice'   => ['label' => '音声会話 (+'. $po_ai_voice . 'pt)', 'class' => 'bg-success']
                                    ];
                                    $currentMode = $modeLabels[$mode] ?? $modeLabels['command'];
                                    
                                    // config から定義済みの音声モデル名を取得
                                    $voices = config('iotdevice.tts_voices', []);
                                    $currentVoiceName = $voices[$iotdevice->tts_voice ?? 'female_1']['name'] ?? '女性 1 (標準)';
                                @endphp
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted"><i class="fa-solid fa-robot me-1"></i>AI応答</span>
                                    <span class="badge {{ $currentMode['class'] }} text-white">{{ $currentMode['label'] }}</span>
                                </div>

                                {{-- 「音声会話」モード時のみ追加表示 --}}
                                @if($mode === 'voice')
                                    <div class="d-flex justify-content-between align-items-center ps-2 border-start">
                                        <span class="text-muted"><i class="fa-solid fa-user-gear me-1"></i>音声モデル</span>
                                        <span class="badge bg-white text-dark border">{{ $currentVoiceName }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center ps-2 border-start">
                                        <span class="text-muted"><i class="fa-solid fa-volume-low me-1"></i>音量</span>
                                        <span class="badge bg-success font-monospace">{{ $iotdevice->speaker_volume ?? 50 }}%</span>
                                    </div>
                                @endif
                            @endif

                        </div>
                    </div>

                </div>
                
                {{-- 編集モード --}}
                <div id="EditArea" class="mx-auto w-75 overflow-hidden" style="display: none;">
                    {{-- 処理ボタン --}}
                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2 mb-3">
                        <button type="button" class="btn btn-primary btn-sm"
                                onclick="openModal('common-modal',{
                                form_id: 'iotdevicesNameUpdateForm',
                                title: 'デバイス設定変更' ,mess: 'このデバイス情報を変更しますか？',
                                cancel_btn: 'キャンセル',confirm_btn: '変更', user_chk: false
                            });">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm"
                                onclick="openModal('common-modal',{
                                form_id: 'iotdevicesDestroyForm',
                                title: 'デバイス削除' ,mess: 'このデバイス削除しますか？',
                                cancel_btn: 'キャンセル',confirm_btn: '削除', user_chk: true
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
                        
                        {{-- デバイス名 --}}
                        <div class="mb-3">
                            <label for="iotdevice_name" class="form-label small fw-bold text-muted mb-1">デバイス名</label>
                            <input type="text" class="form-control form-control-sm" id="iotdevice_name" name="iotdevice_name" value="{{ $iotdevice->name ?? '' }}" placeholder="デバイス名を入力">
                        </div>

                        {{-- ウェイクワード感度設定（70〜100） --}}
                        @if($iotdevice->ww_flag)
                            @php
                                $currentSensitivity = max(70, min(100, $iotdevice->mic_sensitivity ?? 70));
                            @endphp
                            <div class="mb-3">
                                <label for="mic_sensitivity" class="form-label small fw-bold text-muted mb-1">
                                    <i class="fa-solid fa-microphone me-1"></i>ウェイクワード感度
                                </label>
                                <div class="p-2 bg-white rounded border">
                                    <input type="range" class="form-range" id="mic_sensitivity" name="mic_sensitivity" 
                                        min="70" max="100" value="{{ $currentSensitivity }}" 
                                        oninput="document.getElementById('mic_sensitivity_val').innerText = this.value">
                                    <div class="d-flex justify-content-between text-muted align-items-center" style="font-size: 0.75rem;">
                                        <span>70 (低)</span>
                                        <span class="badge bg-primary text-white font-monospace fs-6 px-2">
                                            <span id="mic_sensitivity_val">{{ $currentSensitivity }}</span>
                                        </span>
                                        <span>100 (高)</span>
                                    </div>
                                    <div class="mt-2 text-muted px-1" style="font-size: 10px; line-height: 1.4;">
                                        ※ウェイクワードの検出感度を70〜100の間で調整します。<br>数値が低いと誤反応しやすく、大きいほど厳しく判定します。
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- AI応答モード設定 --}}
                        @if($iotdevice->ai_flag)
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted mb-1">
                                    <i class="fa-solid fa-robot me-1"></i>AI応答モード
                                </label>
                                <div class="card border-0 bg-light p-2 rounded">
                                    <div class="row g-2">
                                        {{-- 操作のみ --}}
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="ai_reply_mode" id="mode_command" value="command" 
                                                {{ ($iotdevice->ai_reply_mode ?? 'command') === 'command' ? 'checked' : '' }} autocomplete="off">
                                            <label class="btn btn-outline-secondary btn-sm w-100 p-2 d-flex flex-column align-items-center h-100 justify-content-between shadow-sm" for="mode_command" style="border-radius: 8px;">
                                                <i class="fa-solid fa-bolt fs-6 my-1"></i>
                                                <span class="fw-bold" style="font-size: 11px;">操作のみ</span>
                                                <span class="badge bg-secondary text-white mt-1" style="font-size: 9px;">+0 pt</span>
                                            </label>
                                        </div>

                                        {{-- 音声応答 --}}
                                        <div class="col-6">
                                            <input type="radio" class="btn-check" name="ai_reply_mode" id="mode_voice" value="voice" 
                                                {{ ($iotdevice->ai_reply_mode ?? '') === 'voice' ? 'checked' : '' }} autocomplete="off">
                                            <label class="btn btn-outline-success btn-sm w-100 p-2 d-flex flex-column align-items-center h-100 justify-content-between shadow-sm" for="mode_voice" style="border-radius: 8px;">
                                                <i class="fa-solid fa-volume-high fs-6 my-1"></i>
                                                <span class="fw-bold" style="font-size: 11px;">音声会話</span>
                                                <span class="badge bg-success text-white mt-1" style="font-size: 9px;">+{{ $po_ai_voice }} pt</span>
                                            </label>
                                        </div>
                                    </div>

                                    {{-- 音声会話専用オプション（「音声会話」選択時のみ連動表示） --}}
                                    <div id="voice_settings_area" class="mt-2 pt-2 border-top" style="{{ ($iotdevice->ai_reply_mode ?? '') === 'voice' ? '' : 'display: none;' }}">
                                        {{-- 音声モデル選択 --}}
                                        <div class="mb-2">
                                            <label for="tts_voice" class="form-label small fw-bold text-muted mb-1" style="font-size: 11px;">
                                                <i class="fa-solid fa-user-gear me-1"></i>音声モデル
                                            </label>
                                            <select class="form-select form-select-sm" id="tts_voice" name="tts_voice">
                                                @foreach(config('common.tts_voices', []) as $key => $voice)
                                                    <option value="{{ $key }}" {{ ($iotdevice->tts_voice ?? 'female_1') === $key ? 'selected' : '' }}>
                                                        {{ $voice['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        {{-- スピーカー音量 --}}
                                        <div>
                                            <label for="speaker_volume" class="form-label small fw-bold text-muted mb-1" style="font-size: 11px;">
                                                <i class="fa-solid fa-volume-low me-1"></i>スピーカー音量
                                            </label>
                                            <div class="p-2 bg-white rounded border">
                                                <input type="range" class="form-range" id="speaker_volume" name="speaker_volume" 
                                                    min="0" max="100" value="{{ $iotdevice->speaker_volume ?? 50 }}" 
                                                    oninput="document.getElementById('speaker_volume_val').innerText = this.value">
                                                <div class="d-flex justify-content-between text-muted align-items-center" style="font-size: 0.75rem;">
                                                    <span>0 (消音)</span>
                                                    <span class="badge bg-success text-white font-monospace fs-6 px-2">
                                                        <span id="speaker_volume_val">{{ $iotdevice->speaker_volume ?? 50 }}</span>%
                                                    </span>
                                                    <span>100 (最大)</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-2 text-muted px-1" style="font-size: 10px; line-height: 1.4;">
                                        ※「操作のみ」は家電制御のみ実行。「音声会話」はボットが声で応答します。
                                    </div>
                                </div>
                            </div>
                        @endif
                    </form>

                    <form id="iotdevicesDestroyForm" method="POST" action="{{ route('iotdevice.destroy') }}">
                        @csrf
                        <input type="hidden" name="iotdevice_id" value="{{ $iotdevice->id ?? '' }}">
                    </form>

                    {{-- デバイスごとの固有処理エリア --}}
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

    {{-- 広告モーダル --}}   
    @include('layouts.adv_popup')
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
        // 音声会話選択時の動的オプション表示切り替え
        //===================================================================
        const modeRadios = document.querySelectorAll('input[name="ai_reply_mode"]');
        const voiceSettingsArea = document.getElementById('voice_settings_area');

        if (voiceSettingsArea) {
            modeRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'voice') {
                        voiceSettingsArea.style.display = 'block';
                    } else {
                        voiceSettingsArea.style.display = 'none';
                    }
                });
            });
        }
    });
</script>