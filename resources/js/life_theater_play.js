/*
==========================================================================
 【ライフシアター 再生画面 スクリプト調整ガイド】
==========================================================================
 各種設定はすぐ下の PLAY_CONFIG 内の値を変更してください。
==========================================================================
*/

// 外部調整用設定パラメータ
const PLAY_CONFIG = {
    SLIDE: {
        DEFAULT_DURATION: 7500,        // スライド切り替え初期時間（ミリ秒）
    },
    BGM: {
        VOLUME: 0.3,                   // BGM音量 (0.0 〜 1.0)
    },
    PARTICLE: {
        COUNT: 20,                     // 生成個数
        DURATION_BASE: 7.5,            // 落下速度の基本値（秒）
        DURATION_RANDOM: 5.5,          // 落下速度のランダム加算幅（秒）
        DELAY_RANDOM: 7,               // 発生遅延のランダム幅（秒）
        USE_IMAGE: false,              // 画像を使用する場合は true に変更
        IMAGES: {                      // 画像モード時の画像パス (USE_IMAGE: true の時)
            sparkle: '/images/sparkle.png',
            sakura:  '/images/sakura.png',
            snow:    '/images/snow.png',
        },
        SYMBOLS: {                     // テキストモード時の絵文字 (USE_IMAGE: false の時)
            sparkle: '✨',
            sakura:  '🌸',
            snow:    '❄️',
        }
    }
};

// 画像のアスペクト比（縦横比）を判定してスライドにクラスを付与する関数
function applyPhotoAspectClasses() {
    document.querySelectorAll('.slide-photo').forEach(img => {
        const checkAspect = () => {
            if (!img.naturalWidth || !img.naturalHeight) return;
            const ratio = img.naturalWidth / img.naturalHeight;
            const slide = img.closest('.slide');
            if (!slide) return;

            slide.classList.remove('aspect-landscape', 'aspect-square', 'aspect-portrait');

            if (ratio > 1.25) {
                slide.classList.add('aspect-landscape'); // 横長画像
            } else if (ratio >= 0.8) {
                slide.classList.add('aspect-square');    // 正方形画像 (1:1)
            } else {
                slide.classList.add('aspect-portrait');  // 縦長画像
            }
        };

        if (img.complete && img.naturalWidth) {
            checkAspect();
        } else {
            img.addEventListener('load', checkAspect);
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    applyPhotoAspectClasses();

    const birthdayContainer = document.querySelector('.birthday');
    const SLIDE_DURATION  = birthdayContainer ? parseInt(birthdayContainer.dataset.slideDuration || PLAY_CONFIG.SLIDE.DEFAULT_DURATION, 10) : PLAY_CONFIG.SLIDE.DEFAULT_DURATION;
    const AUTO_LOOP       = birthdayContainer ? birthdayContainer.dataset.autoLoop === 'true' : false;
    const PARTICLE_EFFECT = birthdayContainer ? birthdayContainer.dataset.particleEffect : 'none';

    const slides = document.querySelectorAll('.slide');
    const pageBtns = document.querySelectorAll('.page-btn');
    const baby = document.getElementById('baby');
    const progress = document.getElementById('progress');
    const modeBtn = document.getElementById('modeBtn');
    const modeIcon = document.getElementById('modeIcon');
    const modeText = document.getElementById('modeText');
    const bgmBtn = document.getElementById('bgmBtn');
    const bgmText = document.getElementById('bgmText');
    const bgm = document.getElementById('bgm');
    const startOverlay = document.getElementById('startOverlay');
    const controlsContainer = document.getElementById('controlsContainer');
    const controlsToggleBtn = document.getElementById('controlsToggleBtn');

    let currentSlide = 0;
    let isAuto = false;
    let isBgmEnabled = true;
    let autoTimeoutId = null;

    function ensureStarted() {
        document.body.classList.add('started');
        if (startOverlay && !startOverlay.classList.contains('hidden')) {
            startOverlay.classList.add('hidden');
        }
    }

    function toggleControlsMenu() {
        if (!controlsContainer || !controlsToggleBtn) return;
        const isCollapsed = controlsContainer.classList.toggle('collapsed');
        controlsToggleBtn.textContent = isCollapsed ? '⚙️' : '✖';
    }

    function startPresentation() {
        ensureStarted();
        goToSlide(0); // 1枚目のアニメーションを0秒からリセット再生する
        setAutoMode(true);
    }

    function tryPlayBGM() {
        if (isBgmEnabled && bgm && bgm.paused) {
            bgm.volume = PLAY_CONFIG.BGM.VOLUME;
            bgm.play().catch(e => console.log('再生エラー:', e));
        }
    }

    function toggleBGM() {
        isBgmEnabled = !isBgmEnabled;
        if (isBgmEnabled) {
            if (bgmBtn) bgmBtn.classList.add('active');
            if (bgmText) bgmText.textContent = 'BGM ON';
            if (isAuto) tryPlayBGM();
        } else {
            if (bgmBtn) bgmBtn.classList.remove('active');
            if (bgmText) bgmText.textContent = 'BGM OFF';
            if (bgm) bgm.pause();
        }
    }

    function goToSlide(index, userAction = false) {
        ensureStarted();
        if (isBgmEnabled) tryPlayBGM();

        if (slides[currentSlide]) slides[currentSlide].classList.remove('active');
        if (pageBtns[currentSlide]) pageBtns[currentSlide].classList.remove('active');

        currentSlide = index;

        if (pageBtns[currentSlide]) pageBtns[currentSlide].classList.add('active');

        const totalSlides = slides.length;
        const percent = totalSlides > 1 ? (currentSlide / (totalSlides - 1)) * 100 : 100;
        if (baby) baby.style.left = `${percent}%`;
        if (progress) progress.style.width = `${percent}%`;

        const activeSlide = slides[currentSlide];
        if (activeSlide) {
            void activeSlide.offsetWidth;
            activeSlide.classList.add('active');
        }

        applyPhotoAspectClasses();

        if (isAuto) startAutoSlide();
    }

    function nextSlide(userAction = false) {
        if (slides.length === 0) return;
        
        if (!userAction && !AUTO_LOOP && currentSlide === slides.length - 1) {
            setAutoMode(false);
            return;
        }

        goToSlide((currentSlide + 1) % slides.length, userAction);
    }

    function prevSlide(userAction = false) {
        if (slides.length === 0) return;
        goToSlide((currentSlide - 1 + slides.length) % slides.length, userAction);
    }

    function toggleMode() {
        ensureStarted();
        setAutoMode(!isAuto);
    }

    function setAutoMode(enableAuto) {
        isAuto = enableAuto;
        if (isAuto) {
            if (modeBtn) modeBtn.classList.add('active');
            if (modeIcon) modeIcon.textContent = '⏸';
            if (modeText) modeText.textContent = 'STOP';
            if (isBgmEnabled) tryPlayBGM();
            startAutoSlide();
        } else {
            if (modeBtn) modeBtn.classList.remove('active');
            if (modeIcon) modeIcon.textContent = '▶';
            if (modeText) modeText.textContent = 'AUTO';
            if (bgm) bgm.pause();
            stopAutoSlide();
        }
    }

    function startAutoSlide() {
        stopAutoSlide();

        const activeSlide = slides[currentSlide];
        const customDuration = activeSlide ? activeSlide.dataset.duration : null;
        
        const duration = (customDuration && parseInt(customDuration, 10) > 0)
            ? parseInt(customDuration, 10)
            : SLIDE_DURATION;

        autoTimeoutId = setTimeout(() => nextSlide(false), duration);
    }

    function stopAutoSlide() {
        if (autoTimeoutId) {
            clearTimeout(autoTimeoutId);
            autoTimeoutId = null;
        }
    }

    // 背景演出（キラキラ・桜・雪）の生成
    function initParticleEffect() {
        if (!birthdayContainer || PARTICLE_EFFECT === 'none') return;

        const pConfig = PLAY_CONFIG.PARTICLE;
        const count = pConfig.COUNT;

        for (let i = 0; i < count; i++) {
            let p;
            if (pConfig.USE_IMAGE && pConfig.IMAGES[PARTICLE_EFFECT]) {
                p = document.createElement('img');
                p.src = pConfig.IMAGES[PARTICLE_EFFECT];
                p.style.cssText = `
                    position: absolute;
                    top: -30px;
                    left: ${Math.random() * 100}%;
                    width: ${12 + Math.random() * 16}px;
                    height: auto;
                    opacity: ${0.4 + Math.random() * 0.6};
                    pointer-events: none;
                    z-index: 10;
                    animation: floatDown ${pConfig.DURATION_BASE + Math.random() * pConfig.DURATION_RANDOM}s linear infinite;
                    animation-delay: ${Math.random() * pConfig.DELAY_RANDOM}s;
                `;
            } else {
                p = document.createElement('div');
                p.textContent = pConfig.SYMBOLS[PARTICLE_EFFECT] || '✨';
                p.style.cssText = `
                    position: absolute;
                    top: -30px;
                    left: ${Math.random() * 100}%;
                    font-size: ${12 + Math.random() * 16}px;
                    opacity: ${0.4 + Math.random() * 0.6};
                    pointer-events: none;
                    z-index: 10;
                    animation: floatDown ${pConfig.DURATION_BASE + Math.random() * pConfig.DURATION_RANDOM}s linear infinite;
                    animation-delay: ${Math.random() * pConfig.DELAY_RANDOM}s;
                `;
            }
            p.className = 'particle-item';
            birthdayContainer.appendChild(p);
        }
    }

    initParticleEffect();

    window.startPresentation = startPresentation;
    window.toggleControlsMenu = toggleControlsMenu;
    window.toggleBGM = toggleBGM;
    window.goToSlide = goToSlide;
    window.nextSlide = nextSlide;
    window.prevSlide = prevSlide;
    window.toggleMode = toggleMode;
});