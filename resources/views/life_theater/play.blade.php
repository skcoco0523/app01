<!DOCTYPE html>
<html lang="ja">
<head>
    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $theater->title ?? 'Life Theater' }}</title>

    {{-- ★ OGP (SNS共有用メタタグ) 設定 ★ --}}
    @php
        // タイムラインの最初のコマに画像があればアイキャッチ画像として使用、なければデフォルトアイコンを使用
        $ogpImage = !empty($timeline[0]['image']) 
            ? $timeline[0]['image'] 
            : asset('img/icon/home_icon_512_512.png');
        
        $ogpTitle = !empty($theater->title) ? $theater->title : 'Life Theater (ライフシアター)';
        $ogpDesc  = !empty($theater->subtitle) ? $theater->subtitle : '思い出を音楽とアニメーションで残す「Life Theater」の作品です。';
    @endphp

    <meta property="og:type" content="website" />
    <meta property="og:title" content="{{ $ogpTitle }}" />
    <meta property="og:description" content="{{ $ogpDesc }}" />
    <meta property="og:image" content="{{ $ogpImage }}" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:site_name" content="{{ config('app.name', 'Life Theater') }}" />

    {{-- ★ Twitter (X) カード設定 ★ --}}
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="{{ $ogpTitle }}" />
    <meta name="twitter:description" content="{{ $ogpDesc }}" />
    <meta name="twitter:image" content="{{ $ogpImage }}" />

    {{-- ★ 追加: Google Publisher Tag (広告ありの場合のみ) ★ --}}
    @unless($is_ad_free ?? true)
    <script async src="https://securepubads.g.doubleclick.net/tag/js/gpt.js"></script>
    <script>
        window.googletag = window.googletag || {cmd: []};
    </script>
    @endunless

    {{-- Vite経由でCSS/JSを読み込み --}}
    @vite(['resources/css/life_theater_play.css', 'resources/js/life_theater_play.js'])
</head>
<body>

<!-- BGMオーディオ要素 -->
@if(!empty($theater->bgm_type))
    <audio id="bgm" src="{{ asset('audio/life_theater/' . $theater->bgm_type) }}" loop preload="auto"></audio>
@endif

<!-- スタート画面オーバーレイ -->
<div class="start-overlay" id="startOverlay">
    {{-- ★ 広告なし（$is_ad_free = true）または変数未定義の場合は直接 startPresentation() を呼ぶ --}}
    @if($is_ad_free ?? true)
        <div class="start-card" onclick="startPresentation()">
            <div class="start-icon"><span>🎵</span><span>💖</span><span>🎶</span></div>
            <div class="start-title">{{ $theater->title ?? 'Life Theater' }}</div>
            <div class="start-btn-text">タップしてスタート</div>
        </div>
    @else
    {{-- ★ 広告あり（$is_ad_free = false）の場合はリワード広告用のボタンにする --}}
        <div class="start-card" id="btn-start-ad" style="cursor: pointer;">
            <div class="start-icon"><span>🎵</span><span>💖</span><span>🎶</span></div>
            <div class="start-title">{{ $theater->title ?? 'Life Theater' }}</div>
            <div class="start-btn-text" id="btn-start-ad-text">広告を見てスタート</div>
            <div style="font-size: 10px; color: #999; margin-top: 8px;">
                ※無料版のため、視聴完了後に作品が再生されます
            </div>
        </div>
    @endif
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
                                                    {!! $isEvent ?  e($speech['name']) : e($speech['name']) !!}
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

{{-- ★ 追加: リワード広告制御のJavaScript（広告ありの場合のみ） ★ --}}
@unless($is_ad_free ?? true)
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const startBtn = document.getElementById('btn-start-ad');
        const startBtnText = document.getElementById('btn-start-ad-text');
        if (!startBtn) return;

        let rewardedSlot = null;
        let rewardPayload = null;
        let isClickTriggered = false;
        let isGranted = false; // ★ 視聴完了フラグ

        function resetButton(text = '広告を見てスタート') {
            startBtn.style.pointerEvents = 'auto';
            startBtn.style.opacity = '1';
            if (startBtnText) startBtnText.innerHTML = text;
            isClickTriggered = false;
        }

        googletag.cmd.push(function() {
            rewardedSlot = googletag.defineOutOfPageSlot(
                '/22639388115/rewarded_web_example',
                googletag.enums.OutOfPageFormat.REWARDED
            );

            if (!rewardedSlot) return;
            rewardedSlot.addService(googletag.pubads());

            // 1. 在庫チェック（広告がない場合のみ即再生バイパス）
            googletag.pubads().addEventListener('slotRenderEnded', function(event) {
                if (event.slot === rewardedSlot && event.isEmpty) {
                    if (isClickTriggered) {
                        console.log("広告在庫がないため直接再生を開始します");
                        startPresentation();
                        resetButton();
                    }
                }
            });

            // 2. 広告準備完了
            googletag.pubads().addEventListener('rewardedSlotReady', function(event) {
                rewardPayload = event;
                if (isClickTriggered) {
                    rewardPayload.makeRewardedVisible();
                }
            });

            // 3. 視聴完了時（最後まで見終えた時だけここが通る）
            googletag.pubads().addEventListener('rewardedSlotGranted', function(event) {
                isGranted = true; // ★ 完走フラグをセット
            });

            // 4. ユーザーが広告を閉じた時の処理
            googletag.pubads().addEventListener('rewardedSlotClosed', function() {
                rewardPayload = null;
                
                if (isGranted) {
                    // ★ 最後まで見た場合のみ再生を開始
                    isGranted = false;
                    startPresentation();
                } else {
                    // ★ 途中でスキップされた場合
                    alert("広告を最後まで視聴しないと再生できません。\n画面を再読み込みします。");
                    location.reload(); // ★ 画面をリロードしてGPTの状態を完全にリセット
                }
            });

            googletag.enableServices();
            googletag.display(rewardedSlot);
        });

        // ボタン押下時
        startBtn.addEventListener('click', function() {
            if (isClickTriggered) return;

            isClickTriggered = true;
            isGranted = false; // ★ 開始時にフラグをクリア
            startBtn.style.pointerEvents = 'none';
            startBtn.style.opacity = '0.7';
            if (startBtnText) startBtnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> 読み込み中...';

            if (rewardPayload) {
                rewardPayload.makeRewardedVisible();
            } else {
                googletag.cmd.push(function() {
                    googletag.pubads().refresh([rewardedSlot]);
                });
            }
        });
    });
</script>
@endunless

</body>
</html>