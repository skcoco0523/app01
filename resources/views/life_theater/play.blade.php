<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $theater->title ?? 'Life Theater' }}</title>

    {{-- Vite経由でCSS/JSを読み込み --}}
    @vite(['resources/css/life_theater_play.css', 'resources/js/life_theater_play.js'])
</head>
<body>

<!-- BGMオーディオ要素 -->
@if(!empty($theater->bgm_type))
    <audio id="bgm" src="{{ asset('audio/life_theater/' . $theater->bgm_type) }}" loop preload="auto"></audio>
@endif

<!-- スタート画面オーバーレイ -->
<div class="start-overlay" id="startOverlay" onclick="startPresentation()">
    <div class="start-card">
        <div class="start-icon"><span>🎵</span><span>💖</span><span>🎶</span></div>
        <div class="start-title">{{ $theater->title ?? 'Life Theater' }}</div>
        <div class="start-btn-text">タップしてスタート</div>
    </div>
</div>

<div class="birthday" 
     data-slide-duration="{{ $slideDuration }}"
     data-auto-loop="{{ $autoLoop ? 'true' : 'false' }}"
     data-particle-effect="{{ $particleEffect }}"
     style="background: {{ config('common.note_colors.'.$theater->theme_color_num.'.code', '#f0f8ff') }};">
     
    <!-- ブランドバッジの動的表示切替 -->
    @if ($showBrandBadge)
        <a href="{{ url('/life-theater') }}" class="brand-badge" target="_blank" rel="noopener">
            ✨ Life Theater
        </a>
    @endif

    <!-- 上部操作コントローラー -->
    <div class="controls-container collapsed" id="controlsContainer">
        <button class="controls-toggle-btn" id="controlsToggleBtn" onclick="toggleControlsMenu()" title="操作メニュー">
            ⚙️
        </button>

        <div class="controls-body">
            <button class="mode-btn" id="modeBtn" onclick="toggleMode()">
                <span id="modeIcon">▶</span> <span id="modeText">AUTO</span>
            </button>
            <button class="mode-btn active" id="bgmBtn" onclick="toggleBGM()">
                🎵 <span id="bgmText">BGM ON</span>
            </button>

            <div class="page-list">
                @foreach ($timeline as $m => $data)
                    <button class="page-btn {{ $loop->first ? 'active' : '' }}" onclick="goToSlide({{ $m }}, true)">{{ $data['label'] }}</button>
                @endforeach
            </div>

            <div class="control-row">
                <button class="nav-btn" onclick="prevSlide(true)" title="前へ">❮</button>
                <button class="nav-btn" onclick="nextSlide(true)" title="次へ">❯</button>
            </div>

            <div style="border-top: 1px solid #ffe4e6; margin: 4px 0; width: 100%;"></div>
            <a href="{{ url('/') }}" class="mode-btn" style="text-decoration: none; background: #f472b6; color: #fff;">
                🏠 アプリTOP
            </a>
        </div>
    </div>

    <!-- スライド一覧 -->
    @foreach ($timeline as $m => $data)
        @php
            $slideConfig    = $data['config'] ?? [];
            $duration       = $slideConfig['duration_override'] ?? '';
            $textPosition   = $slideConfig['text_position'] ?? 'center';
            $transitionType = $slideConfig['transition_type'] ?? 'fade';
            $castList       = $data['cast'] ?? $data['objects'] ?? [];
        @endphp

        <div class="slide slide-{{ $m }} {{ $loop->first ? 'active' : '' }} pos-{{ $textPosition }} trans-{{ $transitionType }}" 
             data-index="{{ $m }}" 
             data-duration="{{ $duration }}">
            
            <div class="slide-title size-{{ $titleSize }}">{{ $data['title'] }}</div>
            @if(!empty($data['subtitle']))
                <div class="slide-subtitle size-{{ $subtitleSize }}">{{ $data['subtitle'] }}</div>
            @endif

            {{-- メイン表示エリア（写真＋右側詳細グループ） --}}
            <div class="slide-main-content">
                {{-- S3画像メイン表示 --}}
                @if (!empty($data['image']))
                    <img src="{{ $data['image'] }}" alt="スライド写真" class="slide-photo" loading="eager">
                @endif

                {{-- 右側（または下部）の詳細情報グループ --}}
                <div class="slide-side-details">
                    {{-- メッセージテキスト --}}
                    @if (!empty($data['text']))
                        <div class="slide-text">{!! nl2br(e($data['text'])) !!}</div>
                    @endif

                    {{-- キャスト・吹き出し（会話リレー＆個別演出config対応） --}}
                    @if (!empty($castList))
                        <div class="birth-info-container">
                            @foreach ($castList as $index => $person)
                                @php
                                    $speeches = $person['speeches'] ?? [$person];
                                @endphp

                                <div class="birth-card">
                                    {{-- 会話ステップごとのアイコン画像 --}}
                                    @foreach ($speeches as $speech)
                                        @if (!empty($speech['image']))
                                            <img src="{{ $speech['image'] }}" alt="{{ $speech['name'] ?? '' }}" 
                                                class="baby-thumb {{ $loop->first ? 'default-thumb' : '' }} anim-{{ $speech['anim_style'] }}" 
                                                style="--start-delay: {{ $speech['start_delay'] }}s; --pop-duration: {{ $speech['duration'] }}s; --pop-scale: {{ $speech['pop_scale'] }};" 
                                                loading="eager">
                                        @endif
                                    @endforeach

                                    {{-- 同一人物の全会話ステップ（吹き出し） --}}
                                    @foreach ($speeches as $speech)
                                        @php
                                            $isEvent = (($speech['type'] ?? '') === 'event');
                                        @endphp

                                        <div class="baby-details pos-balloon-{{ $speech['balloon_pos'] }}" 
                                            style="--start-delay: {{ $speech['start_delay'] }}s; --pop-duration: {{ $speech['duration'] }}s; --pop-scale: {{ $speech['pop_scale'] }};" 
                                            data-is-event="{{ $isEvent ? 'true' : 'false' }}">
                                            @if(!empty($speech['name']))
                                                <div class="baby-name">
                                                    {!! $isEvent ? '💍 ' . e($speech['name']) : e($speech['name']) !!}
                                                </div>
                                            @endif
                                            @if (!empty($speech['text']))
                                                <div class="baby-meta">{!! nl2br(e($speech['text'])) !!}</div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- 日付 --}}
                    @if (!empty($data['date']))
                        <div class="date">{{ $data['date'] }}</div>
                    @endif
                </div>
            </div>

        </div>
    @endforeach

    <!-- 最後の案内スライド（共有リンクから来た非会員ユーザー向け） -->
    @auth
    @else
        <div class="slide slide-cta" data-index="{{ count($timeline) }}">
            <div class="slide-title size-md">思い出を残しませんか？</div>
            <div class="slide-subtitle size-sm">「Life Theater」で写真や想い出を音楽とともに残せます。</div>

            <div class="cta-btn-group">
                <a href="{{ route('register') }}" class="btn-primary-cta">無料で作品を作る</a>
                <a href="{{ url('/') }}" class="btn-secondary-cta">アプリを見る</a>
            </div>
        </div>
    @endauth

    {{-- 下部タイムラインバー --}}
    <div class="timeline-container">
        <div class="timeline-track">
            <div class="timeline-progress" id="progress"></div>
            <div class="baby-runner" id="baby">
                <svg viewBox="0 0 100 80" style="width: 100%; height: 100%;">
                    <circle cx="50" cy="40" r="25" fill="#f472b6" />
                </svg>
            </div>
        </div>
    </div>

</div>

</body>
</html>