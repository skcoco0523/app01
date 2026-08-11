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
                    <form id="iotdevicesNameUpdateForm" method="POST" action="{{ route('iotdevice.update') }}">
                        @csrf
                        <input type="hidden" name="iotdevice_id" value="{{ $iotdevice->id ?? '' }}">
                        
                        <input type="hidden" name="ww_score" value="{{ $iotdevice->ww_score ?? '' }}">
                        <input type="text" class="form-control form-control-sm me-2" name="iotdevice_name" value="{{ $iotdevice->name ?? '' }}" >
                    </form>
                    <form id="iotdevicesDestroyForm" method="POST" action="{{ route('iotdevice.destroy') }}">
                        @csrf
                        <input type="hidden" name="iotdevice_id" value="{{ $iotdevice->id ?? '' }}">
                    </form>
                    
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

                    <?// 音声キーワード登録 ?>
                    <div class="mt-3 text-center border rounded p-2 bg-light">
                        <p class="mb-1 text-muted">
                            <small>音声キーワード登録</small>
                            @if($iotdevice->ww_data)
                                <span class="badge bg-success ms-1">登録済み</span>
                            @else
                                <span class="badge bg-secondary ms-1">未登録</span>
                            @endif
                        </p>
                        
                        <div id="voiceInitialUI" class="d-flex flex-column align-items-center gap-2 mt-1">
                            <div id="voiceStepIndicator" class="text-muted" style="font-size: 0.7rem; display: none;">
                                ステップ: <span id="currentVoiceStep">1</span> / 3
                            </div>
                            <div id="voiceStatusMessage" class="text-primary fw-bold" style="font-size: 0.8rem; height: 1.2rem;"></div>
                            <div id="voiceCountdown" class="display-6 text-danger fw-bold d-none" style="line-height: 1;"></div>

                            <div class="progress w-75 d-none" id="voiceRecordProgress" style="height: 10px;">
                                <div id="voiceRecordProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-danger" role="progressbar" style="width: 0%; transition: none;"></div>
                            </div>
                            
                            <div id="voiceActionButtons" class="d-grid gap-2 justify-content-center">
                                @if($iotdevice->ww_data)
                                    <?// 音声認証スコア（合格ライン）の設定 ?>
                                    <div class="mb-2 text-start border-bottom pb-2">
                                        <label class="form-label small text-muted mb-0">
                                            認証スコア: <span id="rangeValue" class="fw-bold text-primary">{{ $iotdevice->ww_score ?? 80 }}</span>%
                                        </label>
                                        <input type="range" class="form-range" name="ww_score" 
                                            form="iotdevicesNameUpdateForm"
                                            min="0" max="100" step="5" 
                                            value="{{ $iotdevice->ww_score ?? 80 }}"
                                            oninput="document.getElementById('rangeValue').textContent = this.value">
                                        <div class="d-flex justify-content-between text-muted" style="font-size: 0.5rem;">
                                            <span>低(誰でも)</span>
                                            <span>高(厳格)</span>
                                        </div>
                                    </div>

                                    <button type="button" id="voiceClearBtn" class="btn btn-outline-danger btn-sm"
                                        onclick="openModal('common-modal',{
                                            form_id: 'voiceClearForm',
                                            title: '音声情報' ,mess: '登録済みの音声認証情報を削除しますか？',
                                            cancel_btn: 'キャンセル',confirm_btn: '削除', user_chk: true
                                        });">
                                        <i class="fa-solid fa-trash-can"></i> 音声データ削除
                                    </button>
                                    <form id="voiceClearForm" action="{{ route('iotdevice.set_ww_data') }}" method="POST" style="display: none;">
                                        @csrf
                                        <input type="hidden" name="iotdevice_id" value="{{ $iotdevice->id }}">
                                        <input type="hidden" name="clear_voice" value="1">
                                    </form>
                                    <button type="button" id="voiceTestBtn" class="btn btn-outline-info btn-sm">
                                        <i class="fa-solid fa-vial"></i> 判定テスト
                                    </button>
                                @else
                                
                                    <button type="button" id="voiceRecordBtn" class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-microphone"></i> <span id="voiceRecordStatus">録音開始</span>
                                    </button>
                                @endif
                                
                            </div>
                        </div>

                        {{-- プレビューUI (録音後に表示) --}}
                        <div id="voicePreviewUI" class="mt-2" style="display: none;">
                            <div class="d-flex justify-content-center gap-2 mb-2">
                                <button type="button" id="voiceRetakeBtn" class="btn btn-secondary btn-sm">
                                    <i class="fa-solid fa-rotate-left"></i> 録り直し
                                </button>
                            </div>
                            <button type="button" id="voiceSubmitBtn" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-cloud-arrow-up"></i> この内容で分析・登録
                            </button>
                        </div>

                        <form id="voiceUploadForm" action="{{ route('iotdevice.set_ww_data') }}" method="POST" style="display: none;">
                            @csrf
                            <input type="hidden" name="iotdevice_id" value="{{ $iotdevice->id }}">
                            <input type="hidden" name="ww_features_list" id="voiceFeaturesListInput">
                        </form>
                    </div>

                
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

        // 音声録音処理
        const voiceInitialUI = document.getElementById('voiceInitialUI');
        const voicePreviewUI = document.getElementById('voicePreviewUI');
        const voiceRecordBtn = document.getElementById('voiceRecordBtn');
        const voiceRecordStatus = document.getElementById('voiceRecordStatus');
        const voiceRetakeBtn = document.getElementById('voiceRetakeBtn');
        const voiceSubmitBtn = document.getElementById('voiceSubmitBtn');
        const voiceUploadForm = document.getElementById('voiceUploadForm');
        const voiceFeaturesListInput = document.getElementById('voiceFeaturesListInput');
        const voiceTestBtn = document.getElementById('voiceTestBtn');
        const voiceStepIndicator = document.getElementById('voiceStepIndicator');
        const currentVoiceStep = document.getElementById('currentVoiceStep');
        const statusMsg = document.getElementById('voiceStatusMessage');
        const countdownEl = document.getElementById('voiceCountdown');
        const progressBar = document.getElementById('voiceRecordProgressBar');
        const progressArea = document.getElementById('voiceRecordProgress');
        const actionButtons = document.getElementById('voiceActionButtons');

        preloadSounds(["select02", "select07"]);     
        
        let isTestMode = false;
        let voiceStep = 1;
        let currentFeatures = null;
        let recordedFeaturesList = [];

        if (voiceTestBtn) {
            voiceTestBtn.addEventListener('click', async () => {
                isTestMode = true;
                await startRecordingProcess();
            });
        }

        if (voiceRecordBtn) {
            voiceRecordBtn.addEventListener('click', async () => {
            isTestMode = false;
            await startRecordingProcess();
        });
}

        async function startRecordingProcess() {
            const classifierInstance = window.getClassifier();
            await classifierInstance.init();
            
            // --- 修正箇所：共通インフラの取得と有効化 ---
            // app.js の共通関数から取得（未生成なら生成される）
            const audioCtx = window.getSharedAudioContext();

            // 状態に関わらず、クリック直後のこのタイミングで必ず叩き起こす（PWA/スマホ対策）
            if (audioCtx.state === 'suspended') {
                try { 
                    await audioCtx.resume(); 
                    console.log("AudioContext resumed successfully.");
                } catch(e) {
                    console.error("AudioContext resume failed:", e);
                }
            }

            if (!isTestMode) {
                voiceStepIndicator.style.display = 'block';
                currentVoiceStep.textContent = voiceStep;
            }

            if (voiceRecordBtn) voiceRecordBtn.disabled = true; // 存在チェックを追加
            actionButtons.classList.add('d-none');

            let count = 3;
            countdownEl.classList.remove('d-none');
            countdownEl.textContent = count;
            statusMsg.innerHTML = '<span class="text-danger">2秒間</span>の録音準備中...';

            const countdownTimer = setInterval(async () => {
                count--;
                if (count > 0) {
                    countdownEl.textContent = count;
                } else {
                    clearInterval(countdownTimer);
                    countdownEl.classList.add('d-none');
                    await executeVoiceCapture();
                }
            }, 1000);
        }

        async function executeVoiceCapture() {
            statusMsg.textContent = '2秒間、声を録音しています...';
            progressArea.classList.remove('d-none');
            progressBar.style.transition = 'none';
            progressBar.style.width = '0%';
            
            setTimeout(() => {
                progressBar.style.transition = 'width 2000ms linear';
                progressBar.style.width = '100%';
            }, 10);
            
            SoundManager.play('select02');

            try {
                // run-impulse.js の get_ww_data を使用
                const recordedData = await window.get_ww_data(2000);
                SoundManager.play('select07');
                progressArea.classList.add('d-none');
                statusMsg.textContent = '音声解析中...';

                // run-impulse.js の ww_analyze_execute を使用
                const classifierInstance = window.getClassifier();
                const features = await window.ww_analyze_execute(classifierInstance, recordedData);
                
                currentFeatures = features;
                statusMsg.textContent = '解析完了！';

                if (isTestMode) {
                    await performTestClassification(features);
                } else {
                    voiceInitialUI.style.display = 'none';
                    voicePreviewUI.style.display = 'block';
                    if (voiceStep < 3) {
                        voiceSubmitBtn.innerHTML = `<i class="fa-solid fa-arrow-right"></i> ${voiceStep}回目を確定して次へ`;
                    } else {
                        voiceSubmitBtn.innerHTML = '<i class="fa-solid fa-cloud-arrow-up"></i> 分析・登録';
                    }
                }
            } catch (error) {
                console.error("録音または解析に失敗:", error);
                statusMsg.textContent = '失敗: ' + error.message;
                progressArea.classList.add('d-none');
                if (voiceRecordBtn) voiceRecordBtn.disabled = false;
                actionButtons.classList.remove('d-none');
                if (voiceTestBtn) voiceTestBtn.disabled = false;
            }
        }

        async function performTestClassification(features) {
            statusMsg.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 判定中...';
            
            return new Promise((resolve, reject) => {
                $.ajax({
                    type: "post",
                    url: wwScoreCheckUrl,
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    data: {
                        _token: '{{ csrf_token() }}',
                        iotdevice_id: "{{ $iotdevice->id }}",
                        ww_features: JSON.stringify(features)
                    },
                })
                .done(result => {
                    if (result.success) {
                        const colorClass = result.match ? 'text-success' : 'text-danger';
                        statusMsg.innerHTML = `<span class="${colorClass}">${result.msg}</span>`;
                        
                        setTimeout(() => {
                            statusMsg.textContent = '録音開始ボタンを押してください';
                            actionButtons.classList.remove('d-none');
                            if (voiceTestBtn) voiceTestBtn.disabled = false;
                        }, 1500);
                    } else {
                        statusMsg.textContent = 'エラー: ' + (result.msg || '不明なエラー');
                        actionButtons.classList.remove('d-none');
                        if (voiceTestBtn) voiceTestBtn.disabled = false;
                    }
                    resolve(result);
                })
                .fail((xhr, status, error) => {
                    console.error('API request failed:', error);
                    statusMsg.textContent = '通信失敗';
                    actionButtons.classList.remove('d-none');
                    if (voiceTestBtn) voiceTestBtn.disabled = false;
                    reject(error);
                });
            });
        }

        voiceRetakeBtn.addEventListener('click', () => {
            currentFeatures = null;
            voicePreviewUI.style.display = 'none';
            voiceInitialUI.style.display = 'flex';
            actionButtons.classList.remove('d-none');
            if (voiceRecordBtn) voiceRecordBtn.disabled = false;
            statusMsg.textContent = '録音開始ボタンを押してください';
        });

        voiceSubmitBtn.addEventListener('click', () => {
            recordedFeaturesList.push(currentFeatures);
            
            if (voiceStep < 3) {
                // 次のステップへ
                voiceStep++;
                currentVoiceStep.textContent = voiceStep;
                currentFeatures = null;
                voicePreviewUI.style.display = 'none';
                voiceInitialUI.style.display = 'flex';
                actionButtons.classList.remove('d-none');
                if (voiceRecordBtn) voiceRecordBtn.disabled = false;
                voiceRecordStatus.textContent = `${voiceStep}回目の録音開始`;
                statusMsg.textContent = '次の録音準備ができました';
            } else {
                // 最終提出
                voiceFeaturesListInput.value = JSON.stringify(recordedFeaturesList);
                voiceSubmitBtn.disabled = true;
                voiceSubmitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 最終分析中...';
                voiceUploadForm.submit();
            }
        });

        if (voiceRecordBtn) voiceRecordBtn.disabled = false;
        statusMsg.textContent = '録音開始ボタンを押してください';
    });
</script>
