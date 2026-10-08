@extends('layouts.app')

@section('content')
    <i class="fa-solid fa-angles-left" onclick="window.location='{{ route('life_theater.index') }}'"></i>
    <div class="container py-4">
        <div class="note-header d-flex flex-column mb-2">
            
            <div class="title-text mx-auto w-100 overflow-hidden">
                <div class="d-grid align-items-center mb-2" style="grid-template-columns: 1fr auto 1fr; gap: 10px;">
                    <div></div>
                    <div class="text-center text-ellipsis">
                        <h3 class="mb-0 text-nowrap text-truncate">{{ $theater->title ?? '' }}</h3>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary btn-sm text-nowrap" id="toggleEditModeBtn">
                            <i class="fa-solid fa-gear"></i> <span id="buttonText">設定</span>
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
                            <h5 class="fw-bold mb-0"><i class="fa-solid fa-film me-1"></i> スライド一覧 ({{ count($slides) }}コマ)</h5>
                        </div>
                        
                        <div class="d-flex gap-2 align-items-center mb-3">
                            <a href="{{ route('life_theater.play', ['id' => $theater->id ?? $theater->life_theater_id, 'share_flag' => $share_flag]) }}" 
                               target="_blank" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-play me-1"></i> 再生
                            </a>
                            
                            <button type="button" class="btn btn-success btn-sm"
                                onclick="openModal('life_theater_media-modal', {
                                    media_manage_mode: '1',
                                    media_modal_title_text: '画像ライブラリ（画像を選択して編集）'
                                });" title="ライブラリ"> 
                                <i class="fa-solid fa-plus me-1"></i> ライブラリ
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="openModal('life_theater_slide_add-modal');">
                                <i class="fa-solid fa-plus me-1"></i> コマ追加
                            </button>
                        </div>

                        {{-- タイムライン・コマリスト --}}
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
                                            {{-- 画像ありマーク（高さ・サイズをボタンと統一） --}}
                                            @if($slide->media)
                                                <span class="badge bg-success d-inline-flex align-items-center justify-content-center" style="height: 32px; width: 32px; padding: 0;">
                                                    <i class="fa-solid fa-image"></i>
                                                </span>
                                            @endif

                                            @if(($theater->owner_flag || $theater->admin_flag ?? false))
                                                {{-- 鉛筆ボタン --}}
                                                <button type="button" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center justify-content-center" style="height: 32px; width: 36px; padding: 0;"
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

                                                {{-- 削除ボタン（formに flex ＆ m-0 を指定して高さを一致させる） --}}
                                                <form action="{{ route('life_theater.slide.destroy') }}" method="POST" onsubmit="return confirm('このコマを削除しますか？');" class="d-inline-flex align-items-center m-0">
                                                    @csrf
                                                    <input type="hidden" name="id" value="{{ $slide->id }}">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm d-inline-flex align-items-center justify-content-center" style="height: 32px; width: 36px; padding: 0;">
                                                        <i class="fa-solid fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center text-muted py-4">
                                    スライドがまだ登録されていません。<br>「コマ追加」ボタンから思い出を追加しましょう。
                                </div>
                            @endforelse
                        </div>

                    </div>
                </div>

                {{-- 全体設定モード --}}
                <div id="EditArea" style="display: none;">
                    <div class="d-flex justify-content-center align-items-center flex-wrap gap-2 mb-3">
                        @if(($theater->owner_flag || $theater->admin_flag ?? false) && !$theater->edit_lock_flag)
                            <button type="button" class="btn btn-primary btn-sm"
                                onclick="openModal('common-modal',{
                                    form_id: 'theaterUpdateForm', title: '作品変更' ,mess: 'この作品の設定を変更しますか？',
                                    cancel_btn: 'キャンセル', confirm_btn: '変更', user_chk: false
                                });">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        @endif

                        @if($theater->owner_flag)
                            <button type="button" class="btn btn-primary btn-sm"
                                onclick="openModal('life_theater_share-modal',{ life_theater_id: '{{ $theater->id ?? '' }}', theater_title: '{{ $theater->title ?? '' }}'});">
                                <i class="fa-solid fa-user-plus"></i>
                            </button>
                        @endif

                        @if($theater->owner_flag)
                            <button type="button" class="btn btn-danger btn-sm" 
                                onclick="openModal('common-modal',{
                                    form_id: 'theaterDestroyForm', title: '作品削除' ,mess: 'この作品を削除しますか？',
                                    cancel_btn: 'キャンセル', confirm_btn: '削除', user_chk: true
                                });">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        @else
                            <button type="button" class="btn btn-danger btn-sm"
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

                        <div class="mb-3">
                            <label class="form-label fw-bold">タイトル</label>
                            <input type="text" class="form-control form-control-sm" name="title" value="{{ $theater->title ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">サブタイトル</label>
                            <input type="text" class="form-control form-control-sm" name="subtitle" value="{{ $theater->subtitle ?? '' }}">
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
                                    <small class="text-muted d-block mt-2" style="font-size: 11px;">
                                        <i class="fa-solid fa-circle-info me-1"></i> 一部機能はプレミアムパックの購入で利用可能になります。<br>
                                    </small>
                                @endif
                            </div>
                        </div>
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
    </div>

    <!-- モーダルインクルード（名称統一） -->
    @include('modals.life_theater_slide_add-modal')
    @include('modals.life_theater_slide_edit-modal')
    @include('modals.life_theater_slide_object-modal')
    @include('modals.life_theater_share-modal')
    @include('modals.life_theater_media-modal')
    
    @include('layouts.adv_popup')

@endsection

<script>
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
</script>