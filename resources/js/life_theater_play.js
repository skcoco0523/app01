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
    // ★ 画像アスペクト比クラスの適用を即時実行
    applyPhotoAspectClasses();

    const birthdayContainer = document.querySelector('.birthday');
    const SLIDE_DURATION  = birthdayContainer ? parseInt(birthdayContainer.dataset.slideDuration || 7500, 10) : 7500;
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

        // スライド切り替え時にも画像比率判定を再実行
        applyPhotoAspectClasses();

        if (isAuto) startAutoSlide();
    }

    function nextSlide(userAction = false) {
        if (slides.length === 0) return;
        
        // ループ判定：自動再生かつループOFFの時、最後のコマで停止
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
        autoTimeoutId = setTimeout(() => nextSlide(false), SLIDE_DURATION);
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

        const particleCount = 20;
        const symbols = { sparkle: '✨', sakura: '🌸', snow: '❄️' };
        const symbol = symbols[PARTICLE_EFFECT] || '✨';

        for (let i = 0; i < particleCount; i++) {
            const p = document.createElement('div');
            p.className = 'particle-item';
            p.textContent = symbol;
            p.style.cssText = `
                position: absolute;
                top: -30px;
                left: ${Math.random() * 100}%;
                font-size: ${12 + Math.random() * 16}px;
                opacity: ${0.4 + Math.random() * 0.6};
                pointer-events: none;
                z-index: 10;
                
                /* ▼ 落下時間を 8秒〜18秒 へ延ばしてゆっくり落とす（元は 4 + Math.random() * 6） */
                animation: floatDown ${8 + Math.random() * 10}s linear infinite;
                
                /* 出現タイミングも少し分散 */
                animation-delay: ${Math.random() * 8}s;
            `;
            birthdayContainer.appendChild(p);
        }
    }

    initParticleEffect();


    // HTML上の onclick 属性から呼出可能にするため window スコープへバインド
    window.startPresentation = startPresentation;
    window.toggleControlsMenu = toggleControlsMenu;
    window.toggleBGM = toggleBGM;
    window.goToSlide = goToSlide;
    window.nextSlide = nextSlide;
    window.prevSlide = prevSlide;
    window.toggleMode = toggleMode;
});