<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $theater->title ?? 'Life Theater' }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { width: 100%; height: 100%; margin: 0; padding: 0; }
        
        body { 
            overflow: hidden; 
            background: #3e332e; 
            font-family: 'Helvetica Neue', Arial, sans-serif; 
            color: #6b5147; 
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* 基本レイアウト：PC・スマホ横画面 (Landscape) */
        .birthday { 
            position: relative; 
            width: 100vw;
            height: calc(100vw * 9 / 16);
            max-height: 100vh;
            max-width: calc(100vh * 16 / 9);
            aspect-ratio: 16 / 9;
            background: {{ config('common.note_colors.'.$theater->theme_color_num.'.code', '#f0f8ff') }}; 
            overflow: hidden; 
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.5);
            transition: all 0.3s ease;
        }

        /* スタート待機用オーバーレイ */
        .start-overlay {
            position: absolute; inset: 0; z-index: 200;
            background: rgba(255, 250, 245, 0.95);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: opacity 0.6s ease, visibility 0.6s ease;
        }
        .start-overlay.hidden { opacity: 0; visibility: hidden; pointer-events: none; }
        .start-card {
            background: #ffffff; padding: 30px 40px; border-radius: 30px;
            border: 3px dashed #ffb6c1; text-align: center;
            box-shadow: 0 10px 30px rgba(232, 138, 147, 0.2);
            animation: pulse-start 1.8s infinite;
            max-width: 90%;
        }
        .start-icon { display: flex; justify-content: center; align-items: center; gap: 12px; font-size: 38px; margin-bottom: 15px; }
        .start-title { font-size: 24px; font-weight: bold; color: #e88a93; margin-bottom: 18px; }
        .start-btn-text {
            background: #f472b6; color: #fff; padding: 12px 28px; border-radius: 25px;
            font-weight: bold; font-size: 15px; display: inline-block;
            box-shadow: 0 4px 12px rgba(244, 114, 182, 0.3);
        }
        @keyframes pulse-start { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.04); } }

        /* 折りたたみ対応コントローラー */
        .controls-container {
            position: absolute; top: 2.5%; right: 2.5%;
            z-index: 100; display: flex; flex-direction: column; align-items: flex-end;
            gap: 6px;
        }
        .controls-toggle-btn {
            background: #ffffffef; color: #e88a93; border: 1px solid #ffe4e6;
            width: 36px; height: 36px; border-radius: 50%; font-size: 16px;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; box-shadow: 0 4px 12px rgba(232, 138, 147, 0.2);
            transition: all 0.2s ease;
        }
        .controls-toggle-btn:hover { background: #ffe4e6; transform: scale(1.05); }

        .controls-body {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            gap: 6px; background: #ffffffef; padding: 8px 10px; border-radius: 18px;
            box-shadow: 0 4px 15px rgba(232, 138, 147, 0.15); border: 1px solid #ffe4e6;
            transition: all 0.3s ease;
        }
        .controls-container.collapsed .controls-body { display: none; }

        .control-row { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; }
        .mode-btn {
            background: #ffe4e6; color: #e88a93; border: none; padding: 5px 10px;
            border-radius: 20px; font-weight: bold; font-size: 11px; cursor: pointer;
            transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 4px;
            width: 100%; max-width: 95px;
        }
        .mode-btn.active { background: #e88a93; color: #fff; }
        .nav-btn {
            background: #fff; color: #6b5147; border: 1px solid #ffd1d7; width: 24px; height: 24px;
            border-radius: 50%; font-weight: bold; cursor: pointer; transition: all 0.2s;
            display: flex; align-items: center; justify-content: center; font-size: 10px;
        }
        .page-list { display: flex; flex-direction: column; gap: 3px; width: 100%; max-height: 120px; overflow-y: auto; }
        .page-btn {
            background: #fff; color: #8b6f63; border: 1px solid #ffd1d7; padding: 3px 8px;
            border-radius: 8px; font-size: 10px; font-weight: bold; cursor: pointer; transition: all 0.2s;
            text-align: center; width: 100%;
        }
        .page-btn.active { background: #f472b6; color: #fff; border-color: #f472b6; }

        /* スライドコンテンツ */
        .slide { 
            position: absolute; inset: 0; width: 100%; height: 100%; 
            display: flex; flex-direction: column; align-items: center; justify-content: flex-start; 
            opacity: 0; visibility: hidden; transition: opacity 0.8s ease; 
            padding-top: 3.5%; padding-bottom: 18%; 
        }
        .slide.active { opacity: 1; visibility: visible; }
        
        .slide-title { font-weight: bold; color: #e88a93; margin-bottom: 0.5vh; text-align: center; }
        .slide-title.size-sm { font-size: clamp(18px, 2.5vw, 24px); }
        .slide-title.size-md { font-size: clamp(22px, 3.5vw, 32px); }
        .slide-title.size-lg { font-size: clamp(28px, 4.8vw, 44px); }

        .slide-subtitle { color: #8b6f63; margin-bottom: 1vh; text-align: center; }
        .slide-subtitle.size-sm { font-size: clamp(12px, 1.4vw, 16px); }
        .slide-subtitle.size-md { font-size: clamp(14px, 1.8vw, 22px); }
        .slide-subtitle.size-lg { font-size: clamp(18px, 2.4vw, 28px); }

        .slide-photo { width: min(60vw, 650px); max-height: 28vh; object-fit: contain; border-radius: 18px; box-shadow: 0 8px 20px rgba(232, 138, 147, 0.15); border: 4px solid #fff; }
        .slide-text { font-size: clamp(13px, 1.5vw, 18px); color: #6b5147; background: #ffffffb3; padding: 8px 16px; border-radius: 12px; margin-top: 1.2vh; text-align: center; line-height: 1.5; max-width: 85%; }
        .date { margin-top: 1vh; font-size: 13px; color: #9a8176; }

        /* ==========================================
           マンガ風吹き出し・キャスト演出スタイル
        ========================================== */
        .birth-info-container {
            display: flex; gap: 12px; margin-top: 1.2vh;
            width: min(85%, 800px); justify-content: center; align-items: center;
        }
        .birth-card {
            display: flex; align-items: center; gap: 8px; flex: 1;
            max-width: 320px; background: transparent; border: none; padding: 0; box-shadow: none;
        }
        .birth-card:nth-child(2) { flex-direction: row-reverse; }

        /* イベントカード特有デザイン */
        .birth-card[data-is-event="true"] {
            flex-direction: row; background: linear-gradient(135deg, #fff0f3, #ffe4e6);
            border: 2px solid #f472b6; border-radius: 16px; padding: 8px 12px;
            box-shadow: 0 4px 14px rgba(244, 114, 182, 0.2);
        }
        .birth-card[data-is-event="true"] .baby-details { background: transparent; border: none; box-shadow: none; padding: 0; text-align: left; }
        .birth-card[data-is-event="true"] .baby-details::before,
        .birth-card[data-is-event="true"] .baby-details::after { display: none; }
        .birth-card[data-is-event="true"] .baby-name { color: #e11d48; font-size: clamp(13px, 1.3vw, 15px); }

        .baby-thumb {
            width: 48px; height: 50px; border-radius: 50%; object-fit: cover;
            border: 3px solid #f472b6; box-shadow: 0 4px 10px rgba(0,0,0,0.1); flex-shrink: 0; background: #fff;
        }
        .baby-details {
            position: relative; background: #ffffff; border: 2px solid #ffcad4;
            border-radius: 16px; padding: 6px 12px; box-shadow: 0 4px 12px rgba(232, 138, 147, 0.15);
            flex-grow: 1; text-align: left;
        }
        .birth-card:nth-child(2) .baby-details { text-align: right; }

        /* 吹き出し矢印 */
        .birth-card:nth-child(1) .baby-details::before,
        .birth-card:nth-child(3) .baby-details::before {
            content: ''; position: absolute; left: -10px; top: 50%; transform: translateY(-50%);
            border-style: solid; border-width: 7px 10px 7px 0; border-color: transparent #ffcad4 transparent transparent;
        }
        .birth-card:nth-child(1) .baby-details::after,
        .birth-card:nth-child(3) .baby-details::after {
            content: ''; position: absolute; left: -7px; top: 50%; transform: translateY(-50%);
            border-style: solid; border-width: 6px 9px 6px 0; border-color: transparent #ffffff transparent transparent;
        }
        .birth-card:nth-child(2) .baby-details::before {
            content: ''; position: absolute; right: -10px; top: 50%; transform: translateY(-50%);
            border-style: solid; border-width: 7px 0 7px 10px; border-color: transparent transparent transparent #ffcad4;
        }
        .birth-card:nth-child(2) .baby-details::after {
            content: ''; position: absolute; right: -7px; top: 50%; transform: translateY(-50%);
            border-style: solid; border-width: 6px 0 6px 9px; border-color: transparent transparent transparent #ffffff;
        }

        .baby-name { font-size: clamp(12px, 1.2vw, 15px); font-weight: bold; color: #e88a93; margin-bottom: 2px; }
        .baby-meta { font-size: clamp(11px, 1.0vw, 13px); color: #5a4037; font-weight: bold; line-height: 1.35; }

        /* プロフィールカードのぽわん演出 */
        body.started .slide.active .birth-card:nth-child(1) { animation: pop-to-center-left 2s cubic-bezier(0.34, 1.56, 0.64, 1) 0.5s 1 normal forwards; }
        body.started .slide.active .birth-card:nth-child(2) { animation: pop-to-center-right 2s cubic-bezier(0.34, 1.56, 0.64, 1) 2.5s 1 normal forwards; }
        body.started .slide.active .birth-card:nth-child(3) { animation: pop-to-center-middle 2s cubic-bezier(0.34, 1.56, 0.64, 1) 4.5s 1 normal forwards; }
        .slide:not(.active) .birth-card { animation: none !important; }

        @keyframes pop-to-center-left { 0%, 100% { transform: translate(0, 0) scale(1); z-index: 1; } 30%, 75% { transform: translate(20%, -20px) scale(1.15); z-index: 50; } }
        @keyframes pop-to-center-right { 0%, 100% { transform: translate(0, 0) scale(1); z-index: 1; } 30%, 75% { transform: translate(-20%, -20px) scale(1.15); z-index: 50; } }
        @keyframes pop-to-center-middle { 0%, 100% { transform: translate(0, 0) scale(1); z-index: 1; } 30%, 75% { transform: translateY(-20px) scale(1.15); z-index: 50; } }

        /* タイムライン */
        .timeline-container { 
            position: absolute; bottom: 6%; left: 50%; transform: translateX(-50%); 
            width: 75%; max-width: 520px; display: flex; flex-direction: column; gap: 4px; 
            background: #fff0f3ef; border: 1px solid #ffcad4; padding: 8px 20px; border-radius: 20px; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); z-index: 90;
        }
        .timeline-track { position: relative; width: 100%; height: 6px; background: #ffe4e6; border-radius: 3px; }
        .timeline-progress { position: absolute; left: 0; top: 0; height: 100%; width: 0%; background: #f472b6; border-radius: 3px; transition: width 0.8s ease-in-out; }
        .baby-runner { 
            position: absolute; top: -32px; left: 0%; transform: translateX(-50%); 
            width: 44px; height: 34px; transition: left 0.8s ease-in-out;
            filter: drop-shadow(0px 2px 3px rgba(107, 81, 71, 0.3));
        }

        /* スマホ縦画面 (Portrait) */
        @media (orientation: portrait) and (max-width: 768px) {
            .birthday { width: 100vw; height: 100vh; max-width: 100vw; max-height: 100vh; aspect-ratio: auto; box-shadow: none; }
            .slide { padding-top: 70px; padding-bottom: 120px; padding-left: 15px; padding-right: 15px; }
            .slide-title.size-sm { font-size: clamp(16px, 5vw, 22px); }
            .slide-title.size-md { font-size: clamp(20px, 6vw, 28px); }
            .slide-title.size-lg { font-size: clamp(24px, 7vw, 34px); }

            .slide-subtitle.size-sm { font-size: clamp(12px, 3.5vw, 15px); }
            .slide-subtitle.size-md { font-size: clamp(14px, 4vw, 18px); }
            .slide-subtitle.size-lg { font-size: clamp(16px, 4.8vw, 22px); }

            .slide-photo { width: 88vw; max-height: 35vh; }
            .slide-text { font-size: clamp(13px, 3.8vw, 16px); max-width: 95%; }

            .birth-info-container { flex-direction: column; width: 95%; }
            .birth-card:nth-child(2) { flex-direction: row; }

            .controls-container { top: 12px; right: 12px; }
            .controls-body { padding: 6px 8px; max-width: 110px; }
            .page-list { max-height: 80px; }
            .timeline-container { bottom: 3.5%; width: 88%; padding: 6px 15px; }
        }
    </style>
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

<div class="birthday">

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
                @foreach ($timeline as $m =>$data)
                    <button class="page-btn {{ $loop->first ? 'active' : '' }}" onclick="goToSlide({{ $m }}, true)">{{ $data['label'] }}</button>
                @endforeach
            </div>

            <div class="control-row">
                <button class="nav-btn" onclick="prevSlide(true)" title="前へ">❮</button>
                <button class="nav-btn" onclick="nextSlide(true)" title="次へ">❯</button>
            </div>
        </div>
    </div>

    <!-- スライド一覧 -->
    @foreach ($timeline as $m =>$data)
        <div class="slide slide-{{ $m }} {{ $loop->first ? 'active' : '' }}" data-index="{{ $m }}">
            
            <div class="slide-title size-{{ $titleSize }}">{{ $data['title'] }}</div>
            @if(!empty($data['subtitle']))
                <div class="slide-subtitle size-{{ $subtitleSize }}">{{ $data['subtitle'] }}</div>
            @endif

            {{-- S3画像メイン表示 --}}
            @if (!empty($data['image']))
                <img src="{{ $data['image'] }}" alt="スライド写真" class="slide-photo" loading="eager">
            @endif

            {{-- メッセージテキスト --}}
            @if (!empty($data['text']))
                <div class="slide-text">{!! nl2br(e($data['text'])) !!}</div>
            @endif

            {{-- ★ キャスト・吹き出し・イベント（オブジェクト）演出の動的描画 --}}
            @if (!empty($data['objects']))
                <div class="birth-info-container">
                    @foreach ($data['objects'] as $obj)
                        @php $isEvent = ($obj['type'] === 'event'); @endphp
                        
                        <div class="birth-card" data-is-event="{{ $isEvent ? 'true' : 'false' }}">
                            @if (!empty($obj['image']))
                                <img src="{{ $obj['image'] }}" alt="{{ $obj['name'] }}" class="baby-thumb" loading="eager">
                            @endif
                            <div class="baby-details">
                                @if(!empty($obj['name']))
                                    <div class="baby-name">
                                        {!! $isEvent ? '💍 ' . e($obj['name']) : e($obj['name']) !!}
                                    </div>
                                @endif
                                @if (!empty($obj['text']))
                                    <div class="baby-meta">{!! nl2br(e($obj['text'])) !!}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- 日付 --}}
            @if (!empty($data['date']))
                <div class="date">{{ $data['date'] }}</div>
            @endif

        </div>
    @endforeach

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

<script>
    const slides = document.querySelectorAll('.slide');
    const pageBtns = document.querySelectorAll('.page-btn');
    const baby = document.getElementById('baby');
    const progress = document.getElementById('progress');
    const modeBtn = document.getElementById('modeBtn');
    const modeIcon = document.getElementById('modeIcon');
    const modeText = document.getElementById('modeText');
    const bgm = document.getElementById('bgm');
    const bgmBtn = document.getElementById('bgmBtn');
    const bgmText = document.getElementById('bgmText');
    const startOverlay = document.getElementById('startOverlay');

    let currentSlide = 0;
    let isAuto = false;
    let isBgmEnabled = true;

    const SLIDE_DURATION = {{ $slideDuration }};
    let autoTimeoutId = null;

    function toggleControlsMenu() {
        const container = document.getElementById('controlsContainer');
        const toggleBtn = document.getElementById('controlsToggleBtn');
        if (container) {
            container.classList.toggle('collapsed');
            if (container.classList.contains('collapsed')) {
                toggleBtn.textContent = '⚙️';
            } else {
                toggleBtn.textContent = '✖';
            }
        }
    }

    function startPresentation() {
        document.body.classList.add('started');
        if (startOverlay) startOverlay.classList.add('hidden');
        setAutoMode(true);
    }

    function tryPlayBGM() {
        if (isBgmEnabled && bgm && bgm.paused) {
            bgm.volume = 0.3;
            bgm.play().catch(e => console.log('再生エラー:', e));
        }
    }

    function toggleBGM() {
        isBgmEnabled = !isBgmEnabled;
        if (isBgmEnabled) {
            bgmBtn.classList.add('active');
            bgmText.textContent = 'BGM ON';
            if (isAuto) tryPlayBGM();
        } else {
            bgmBtn.classList.remove('active');
            bgmText.textContent = 'BGM OFF';
            if (bgm) bgm.pause();
        }
    }

    function goToSlide(index, userAction = false) {
        document.body.classList.add('started');
        if (startOverlay && !startOverlay.classList.contains('hidden')) {
            startOverlay.classList.add('hidden');
        }

        if (isBgmEnabled) tryPlayBGM();

        slides[currentSlide].classList.remove('active');
        if (pageBtns[currentSlide]) pageBtns[currentSlide].classList.remove('active');

        currentSlide = index;

        slides[currentSlide].classList.add('active');
        if (pageBtns[currentSlide]) pageBtns[currentSlide].classList.add('active');

        const totalSlides = slides.length;
        const percent = totalSlides > 1 ? (currentSlide / (totalSlides - 1)) * 100 : 100;

        baby.style.left = `${percent}%`;
        progress.style.width = `${percent}%`;

        // ぽわん演出のリセットとリスタート
        const activeSlide = slides[currentSlide];
        activeSlide.classList.remove('active');
        void activeSlide.offsetWidth;
        activeSlide.classList.add('active');

        if (isAuto) startAutoSlide();
    }

    function nextSlide(userAction = false) {
        let nextIndex = (currentSlide + 1) % slides.length;
        goToSlide(nextIndex, userAction);
    }

    function prevSlide(userAction = false) {
        let prevIndex = (currentSlide - 1 + slides.length) % slides.length;
        goToSlide(prevIndex, userAction);
    }

    function toggleMode() {
        document.body.classList.add('started');
        if (startOverlay && !startOverlay.classList.contains('hidden')) {
            startOverlay.classList.add('hidden');
        }
        setAutoMode(!isAuto);
    }

    function setAutoMode(enableAuto) {
        isAuto = enableAuto;
        if (isAuto) {
            modeBtn.classList.add('active');
            modeIcon.textContent = '⏸';
            modeText.textContent = 'STOP';
            if (isBgmEnabled) tryPlayBGM();
            startAutoSlide();
        } else {
            modeBtn.classList.remove('active');
            modeIcon.textContent = '▶';
            modeText.textContent = 'AUTO';
            if (bgm) bgm.pause();
            stopAutoSlide();
        }
    }

    function startAutoSlide() {
        stopAutoSlide();
        autoTimeoutId = setTimeout(() => {
            nextSlide(false);
        }, SLIDE_DURATION);
    }

    function stopAutoSlide() {
        if (autoTimeoutId) {
            clearTimeout(autoTimeoutId);
            autoTimeoutId = null;
        }
    }
</script>
</body>
</html>