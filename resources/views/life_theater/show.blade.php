@extends('layouts.app')

@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location='{{ route('life_theater.index') }}'"></i>
        <div class="note-header d-flex flex-column mb-2">
            
            <div class="title-text mx-auto w-100 overflow-hidden">
                <div class="d-grid align-items-center mb-2" style="grid-template-columns: 1fr auto 1fr; gap: 10px;">
                    <div></div>
                    <div class="text-center text-ellipsis">
                        
                        <h3 class="mb-0 text-nowrap text-truncate">
                            {{ $theater->title ?? '' }}
                        </h3>
                    </div>
                    
                    {{-- 設定ボタン・プランバッジ --}}
                    <div class="text-end d-flex align-items-center justify-content-end gap-1">
                        @if ($is_premium)
                            <span class="badge bg-warning text-dark d-inline-flex align-items-center" style="font-size: 11px;">
                                <i class="fa-solid fa-crown me-1"></i>Premium
                            </span>
                        @else
                            {{-- ★修正: 専用モーダルをAPI経由で開く --}}
                            <button type="button" class="badge bg-secondary border-0 d-inline-flex align-items-center" style="font-size: 11px; cursor: pointer;"
                                onclick="openPremiumModal();" title="プレミアムプランにアップグレード">
                                FREE <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 9px;"></i>
                            </button>
                        @endif
                        {{-- 設定ボタンに btn-action-sm を適用 --}}
                        <button type="button" class="btn btn-secondary btn-action-sm" id="toggleEditModeBtn">
                            <i class="fa-solid fa-gear me-1"></i> <span id="buttonText">設定</span>
                        </button>
                    </div>
                </div>

                {{-- 表示・タイムラインモード --}}
                <div id="DisplayArea">
                    <div class="p-3 mb-3 border rounded shadow-sm" style="background-color: {{ config('common.note_colors.'.$theater->theme_color_num.'.code', '#fff') }}; min-height: 200px;">
                        <p class="text-muted mb-1">{{ $theater->subtitle ?? 'サブタイトルなし' }}</p>
                        <div class="small text-secondary mb-3">
                            <span><i class="fa-solid fa-music me-1"></i> BGM: {{ $theater->bgm_type ?? '未設定' }}</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0"><i class="fa-solid fa-film me-1"></i> スライド一覧 ({{ count($slides) }}スライド)</h5>
                        </div>
                        
                        {{-- ★修正：各アクションボタンに btn-action-sm を適用 --}}
                        <div class="d-flex w-100 gap-2 align-items-center mb-3">
                            <a href="{{ route('life_theater.play', ['id' => $theater->id ?? $theater->life_theater_id, 'share_flag' => $share_flag]) }}" 
                               target="_blank" class="btn btn-primary btn-action-sm flex-fill">
                                <i class="fa-solid fa-play me-1"></i> 再生
                            </a>
                            @if(($theater->owner_flag || $theater->admin_flag ?? false))
                                <button type="button" class="btn btn-success btn-action-sm flex-fill"
                                    onclick="openModal('life_theater_media-modal', {
                                        media_manage_mode: '1',
                                        media_modal_title_text: '画像ライブラリ（選択して編集）'
                                    });" title="ライブラリ"> 
                                    <i class="fa-solid fa-photo-film me-1"></i> ライブラリ
                                </button>

                                {{-- 直接モーダルを開かず、チェック関数を呼ぶ --}}
                                <button type="button" class="btn btn-danger btn-action-sm flex-fill"
                                    onclick="checkAndOpenSlideAddModal();">
                                    <i class="fa-solid fa-plus me-1"></i> スライド追加
                                </button>
                            @endif
                        </div>

                        {{-- タイムライン・スライドリスト --}}
                        <div class="d-flex flex-column gap-2">
                            @forelse ($slides as $index => $slide)
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body p-3 d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center gap-2 overflow-hidden">
                                            <span class="badge bg-secondary">#{{ $index + 1 }}</span>
                                            @if($slide->label)
                                                <span class="badge bg-info text-dark">{{ $slide->label }}</span>
                                            @endif
                                            <div class="text-truncate">
                                                <strong class="d-block text-truncate">{{ $slide->title ?? 'タイトルなし' }}</strong>
                                                @if($slide->subtitle)
                                                    <small class="text-muted d-block text-truncate">{{ $slide->subtitle }}</small>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-2">
                                            {{-- 画像ありマーク --}}
                                            @if($slide->media)
                                                <span class="badge bg-success d-inline-flex align-items-center justify-content-center">
                                                    <i class="fa-solid fa-image"></i>
                                                </span>
                                            @endif

                                            @if(($theater->owner_flag || $theater->admin_flag ?? false))
                                                {{-- ★修正：編集ボタンに btn-icon-square を適用 --}}
                                                <button type="button" class="btn btn-outline-primary btn-icon-square"
                                                    data-slide="{{ json_encode([
                                                        'edit_slide_id'          => (string)$slide->id,
                                                        'edit_slide_label'       => (string)($slide->label ?? ''),
                                                        'edit_slide_slide_date'  => (string)($slide->slide_date ?? ''),
                                                        'edit_slide_title'       => (string)($slide->title ?? ''),
                                                        'edit_slide_subtitle'    => (string)($slide->subtitle ?? ''),
                                                        'edit_slide_content'     => (string)($slide->content ?? ''),
                                                        'edit_slide_media_id'    => (string)($slide->life_theater_media_id ?? ''),
                                                        'edit_slide_media_name'  => (string)($slide->media->name ?? '未設定'),
                                                        'edit_slide_media_url'   => (string)($slide->media->image_s3_key ?? ''),
                                                        'edit_slide_config_data' => $slide->parsed_config ?? []
                                                    ]) }}"
                                                    onclick="openModal('life_theater_slide_edit-modal', JSON.parse(this.dataset.slide));">
                                                    <i class="fa-solid fa-pen"></i>
                                                </button>

                                                {{-- ★修正：削除ボタンに btn-icon-square を適用 --}}
                                                <form action="{{ route('life_theater.slide.destroy') }}" method="POST" onsubmit="return confirm('このスライドを削除しますか？');" class="d-inline-flex align-items-center m-0 p-0">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $slide->id }}">
                                                    <button type="submit" class="btn btn-danger btn-icon-square">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    スライドがまだ登録されていません。<br>「スライド追加」ボタンから思い出を追加しましょう。
                                </div>
                            @endforelse
                        </div>

                    </div>
                </div>

                {{-- 全体設定モード --}}
                <div id="EditArea" style="display: none;">
                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2 mb-3">
                        @if(($theater->owner_flag || $theater->admin_flag ?? false) && !$theater->edit_lock_flag)
                            
                            <button type="button" class="btn btn-outline-primary btn-icon-square"
                                onclick="openModal('common-modal',{
                                    form_id: 'theaterUpdateForm', title: '作品変更' ,mess: 'この作品の設定を変更しますか？',
                                    cancel_btn: 'キャンセル', confirm_btn: '変更', user_chk: false
                                });">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        
                            {{-- URL発行（公開管理）ボタン --}}
                            <button type="button" class="btn btn-outline-success btn-icon-square"
                                onclick="openModal('life_theater_public_link-modal', { public_link_theater_id: '{{ $theater->id ?? $theater->life_theater_id }}' });" title="公開URLの発行・管理">
                                <i class="fa-solid fa-share-nodes"></i>
                            </button>
                        @endif

                        @if($theater->owner_flag)
                            <button type="button" class="btn btn-outline-primary btn-icon-square"
                                onclick="openModal('life_theater_share-modal',{ life_theater_id: '{{ $theater->id ?? '' }}', theater_title: '{{ $theater->title ?? '' }}'});">
                                <i class="fa-solid fa-user-plus"></i>
                            </button>
                        @endif

                        @if($theater->owner_flag)
                            <button type="button" class="btn btn-danger btn-icon-square"
                                onclick="openModal('common-modal',{
                                    form_id: 'theaterDestroyForm', title: '作品削除' ,mess: 'この作品を削除しますか？',
                                    cancel_btn: 'キャンセル', confirm_btn: '削除', user_chk: true
                                });">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        @else
                            <button type="button" class="btn btn-danger btn-icon-square"
                                onclick="openModal('common-modal',{
                                    form_id: 'theaterUnShareForm', title: '共有解除' ,mess: 'この作品の共有を解除しますか？',
                                    cancel_btn: 'キャンセル', confirm_btn: '解除', user_chk: true
                                });">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        @endif
                    </div>

                    {{-- 作品基本情報変更フォーム --}}
                    <form id="theaterUpdateForm" method="POST" action="{{ route('life_theater.update') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $theater->id ?? $theater->life_theater_id }}">
                        <input type="hidden" name="share_flag" value="{{ $input['share_flag'] ?? '' }}">

                        {{-- タイトル（文字数カウンター追加） --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-bold mb-0">タイトル <span class="text-danger">*</span></label>
                                <span class="text-muted" style="font-size: 10px;"><span id="show_theater_title_count">0</span>/30</span>
                            </div>
                            <input type="text" class="form-control form-control-sm" id="show_theater_title" name="title" value="{{ $theater->title ?? '' }}" maxlength="30" required oninput="updateCharCount('show_theater_title', 'show_theater_title_count')">
                        </div>

                        {{-- サブタイトル（文字数カウンター追加） --}}
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">サブタイトル</label>
                                <span class="text-muted" style="font-size: 10px;"><span id="show_theater_subtitle_count">0</span>/30</span>
                            </div>
                            <input type="text" class="form-control form-control-sm" id="show_theater_subtitle" name="subtitle" value="{{ $theater->subtitle ?? '' }}" maxlength="30" oninput="updateCharCount('show_theater_subtitle', 'show_theater_subtitle_count')">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">BGM</label>
                            <select class="form-select form-select-sm" name="bgm_type">
                                <option value="Tiny_Steps.mp3" {{ ($theater->bgm_type ?? '') == 'Tiny_Steps.mp3' ? 'selected' : '' }}>Tiny Steps</option>
                                <option value="Happy_Day.mp3" {{ ($theater->bgm_type ?? '') == 'Happy_Day.mp3' ? 'selected' : '' }}>Happy Day</option>
                                <option value="Gentle_Memories.mp3" {{ ($theater->bgm_type ?? '') == 'Gentle_Memories.mp3' ? 'selected' : '' }}>Gentle Memories</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">テーマカラー</label>
                            <div id="color-palette" class="d-flex flex-nowrap gap-3 p-2 border rounded bg-light" style="overflow-x: auto; scrollbar-width: none;">
                                @foreach(config('common.note_colors', []) as $key => $color)
                                    <div class="color-circle flex-shrink-0" data-value="{{ $key }}" data-code="{{ $color['code'] }}"
                                        style="background-color: {{ $color['code'] }}; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; border: 2px solid #fff; box-shadow: 0 0 4px rgba(0,0,0,0.2); transition: transform 0.2s;">
                                    </div>
                                @endforeach
                            </div>
                            <input type="hidden" name="theme_color_num" id="theme_color_num" value="{{ $theater->theme_color_num ?? 0 }}">
                        </div>

                        {{-- 再生表示設定（モデル定義をコントローラー経由で動的描画） --}}
                        <hr class="my-4">
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label fw-bold mb-0">
                                    再生表示設定
                                </label>
                                <span class="badge bg-secondary">再生カスタマイズ</span>
                            </div>

                            <div class="p-3 border rounded bg-light">
                                @foreach ($config_definitions as $key => $def)
                                    @php
                                        $currentVal = $config_values[$key] ?? $def['default'];
                                        $isDisabled = ($def['premium'] ?? false) && !$is_premium;
                                    @endphp

                                    <div class="mb-3">
                                        <label class="form-label small fw-bold mb-1">
                                            @if($def['premium'] ?? false)
                                                <i class="fa-solid fa-crown text-warning me-1"></i>
                                            @endif
                                            {{ $def['label'] }}
                                        </label>
                                        <select class="form-select form-select-sm" name="config_data[{{ $key }}]" {{ $isDisabled ? 'disabled' : '' }}>
                                            @foreach ($def['options'] as $val => $label)
                                                <option value="{{ $val }}" {{ (string)$currentVal === (string)$val ? 'selected' : '' }}>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if (!empty($def['description']))
                                            <small class="text-muted d-block mt-1" style="font-size: 11px;">
                                                {{ $def['description'] }}
                                            </small>
                                        @endif
                                    </div>
                                @endforeach

                                @if (!$is_premium)
                                    <div class="mt-3 pt-2 border-top text-center">
                                        <small class="text-muted d-block mb-2" style="font-size: 11px;">
                                            <i class="fa-solid fa-circle-info me-1"></i> 一部機能はプレミアムパックの購入で利用可能になります。
                                        </small>
                                        
                                        {{-- ★修正: 専用モーダルをAPI経由で開く --}}
                                        <button type="button" class="btn btn-warning btn-action-sm w-100 fw-bold shadow-sm"
                                            onclick="openPremiumModal();">
                                            <i class="fa-solid fa-crown me-1"></i> プレミアム機能を解放
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </form>

                    {{-- プレミアム購入・解放フォーム --}}
                    <form id="theaterPremiumBuyForm" method="POST" action="{{ route('life_theater.update') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $theater->id ?? $theater->life_theater_id }}">
                        <input type="hidden" name="plan_type" value="premium">
                    </form>

                    {{-- 削除フォーム --}}
                    <form id="theaterDestroyForm" method="POST" action="{{ route('life_theater.destroy') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $theater->id ?? $theater->life_theater_id }}">
                    </form>

                    {{-- 共有解除フォーム --}}
                    <form id="theaterUnShareForm" method="POST" action="{{ route('life_theater.unshare') }}">
                        @csrf
                        <input type="hidden" name="id" value="{{ $theater->id ?? $theater->life_theater_id }}">
                    </form>
                </div>

            </div>
        </div>
    

    <!-- モーダルインクルード（名称統一） -->
    @include('modals.life_theater_slide_add-modal')
    @include('modals.life_theater_slide_edit-modal')
    @include('modals.life_theater_slide_object-modal')
    @include('modals.life_theater_share-modal')
    @include('modals.life_theater_media-modal')
    @include('modals.life_theater_public_link-modal')
    @include('modals.life_theater_premium-modal')
    
    @include('layouts.adv_popup')

@endsection

<script>
    if (typeof window.updateCharCount !== 'function') {
        window.updateCharCount = function(inputId, countId) {
            const input = document.getElementById(inputId);
            const count = document.getElementById(countId);
            if (input && count) {
                count.textContent = input.value.length;
            }
        };
    }

    function refreshTheaterCharCounts() {
        updateCharCount('show_theater_title', 'show_theater_title_count');
        updateCharCount('show_theater_subtitle', 'show_theater_subtitle_count');
    }

    document.addEventListener('DOMContentLoaded', function () {
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

        // 初期表示時の文字数を更新
        refreshTheaterCharCounts();

        // テーマカラー選択
        const colorInput = document.getElementById('theme_color_num');

        function selectColor(val) {
            document.querySelectorAll('.color-circle').forEach(c => {
                const isTarget = c.getAttribute('data-value') == val;
                c.style.borderColor = isTarget ? '#000' : '#fff';
                c.style.transform = isTarget ? 'scale(1.1)' : 'scale(1)';
            });
            if (colorInput) colorInput.value = val;
        }

        document.querySelectorAll('.color-circle').forEach(circle => {
            circle.addEventListener('click', function() { selectColor(this.getAttribute('data-value')); });
        });

        if (colorInput && colorInput.value !== "") selectColor(colorInput.value);
    });

    // ==========================================
    // ★ 追加: スライド追加時の上限チェックと枠拡張処理
    // ==========================================
    let currentSlideCount = {{ $current_slide_count ?? 0 }};
    let maxSlideLimit     = {{ $max_slide_limit ?? 10 }};
    const expandSlideCost   = {{ $po_life_theater_slide_cost ?? 300 }};
    const expandSlideAmount = {{ $po_life_theater_slide_amount ?? 5 }};

    function checkAndOpenSlideAddModal() {
        if (currentSlideCount >= maxSlideLimit) {
            openModal('common-modal', {
                title: 'スライド登録枠の拡張',
                mess: `スライドの登録上限（${maxSlideLimit}枚）に達しています。\n\n${expandSlideCost}ポイントを使用して、登録枠を「${expandSlideAmount}枚」追加しますか？`,
                cancel_btn: 'キャンセル',
                confirm_btn: '枠を追加する',
                user_chk: false,
                onConfirm: function() {
                    executeExpandSlideLimit();
                }
            });
        } else {
            openModal('life_theater_slide_add-modal');
        }
    }

    async function executeExpandSlideLimit() {
        const theaterId = "{{ $theater->id ?? $theater->life_theater_id ?? '' }}";
        if (!theaterId) return;

        try {
            const res = await $.ajax({
                type: "POST",
                url: "{{ route('api.life_theater.slide.expand') }}",
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                data: { life_theater_id: theaterId }
            });

            if (res.status === 'success') {
                if (typeof showNotification === 'function') {
                    showNotification(res.message, "success", 2000);
                } else {
                    alert(res.message);
                }
                
                if (res.data && res.data.new_limit) {
                    maxSlideLimit = res.data.new_limit;
                }
                openModal('life_theater_slide_add-modal');

            } else {
                alert(res.message || "拡張に失敗しました。");
            }
        } catch (err) {
            console.error(err);
            const errMsg = err.responseJSON && err.responseJSON.message ? err.responseJSON.message : '処理中にエラーが発生しました。';
            alert(errMsg);
        }
    }
    // ==========================================
    // ★追加: プレミアム解放モーダル表示（API経由で数値を動的取得）
    // ==========================================
    async function openPremiumModal() {
        const theaterId = "{{ $theater->id ?? $theater->life_theater_id ?? '' }}";
        if (!theaterId) return;

        // モーダルを開き、ボタンを一時的に無効化
        document.getElementById('btn_submit_premium').disabled = true;
        openModal('life_theater_premium-modal');

        try {
            const response = await fetch("{{ route('api.life_theater.premium.info') }}?life_theater_id=" + theaterId, {
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });
            const result = await response.json();

            if (result.status === 'success') {
                const d = result.data;
                
                // 数値の流し込み
                document.getElementById('prem_slide_current').textContent = d.slide.current;
                document.getElementById('prem_slide_max').textContent = d.slide.max;
                document.getElementById('prem_slide_add').textContent = '+' + d.slide.add + 'コマ';

                document.getElementById('prem_media_current').textContent = d.media.current;
                
                document.getElementById('prem_media_max').textContent = d.media.max;
                document.getElementById('prem_media_add').textContent = '+' + d.media.add + '枚';

                document.getElementById('prem_object_current').textContent = d.object.current;
                document.getElementById('prem_object_max').textContent = d.object.max;

                document.getElementById('prem_cost').textContent = new Intl.NumberFormat().format(d.cost);

                // ロード完了でボタンを有効化
                document.getElementById('btn_submit_premium').disabled = false;
            } else {
                alert(result.message || '情報の取得に失敗しました');
                closeModal('life_theater_premium-modal');
            }
        } catch (e) {
            console.error(e);
            alert('通信エラーが発生しました');
            closeModal('life_theater_premium-modal');
        }
    }
</script>