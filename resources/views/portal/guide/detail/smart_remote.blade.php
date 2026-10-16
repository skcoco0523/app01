{{-- 3大特徴・活用シーン共通コンポーネント --}}
<div class="py-3 px-2 px-md-3">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge bg-primary px-2 py-1" style="font-size: 10px;">Smart Home Platform</span>
    </div>
    
    <h2 class="fs-4 fw-bold text-dark mb-2">家中の家電操作を、これひとつに。</h2>
    <p class="text-secondary small lh-lg mb-3">
        {{ config('app.name', 'SK_HOME') }}は、スマホやAI音声で家中の家電を一括コントロールできるスマートホームプラットフォームです。
    </p>
    
    {{-- 3大特徴カード --}}
    <div class="row text-center g-2 mb-3">
        {{-- 1. AI音声操作（青） --}}
        <div class="col-4">
            <div id="card-feature-0" class="feature-card p-2 rounded h-100 d-flex flex-column justify-content-center align-items-center" onclick="switchFeature(0)" style="border: 2px solid #cbd5e1; background-color: #ffffff; cursor: pointer;">
                <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-wand-magic-sparkles fs-6"></i>
                </div>
                <div class="fw-bold text-dark" style="font-size: 10px;">AI音声操作</div>
                <div class="text-muted mt-1 d-none d-md-block" style="font-size: 9px;">声で家電をコントロール</div>
                <span class="badge border mt-1 tap-hint" style="font-size: 8px;">表示中 <i class="fa-solid fa-check"></i></span>
            </div>
        </div>

        {{-- 2. 外出先から操作（緑） --}}
        <div class="col-4">
            <div id="card-feature-1" class="feature-card p-2 rounded h-100 d-flex flex-column justify-content-center align-items-center" onclick="switchFeature(1)" style="border: 2px solid #cbd5e1; background-color: #ffffff; cursor: pointer;">
                <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-globe fs-6"></i>
                </div>
                <div class="fw-bold text-dark" style="font-size: 10px;">外出先から操作</div>
                <div class="text-muted mt-1 d-none d-md-block" style="font-size: 9px;">帰宅前にエアコンON</div>
                <span class="badge border mt-1 tap-hint" style="font-size: 8px;">タップ <i class="fa-solid fa-hand-pointer"></i></span>
            </div>
        </div>

        {{-- 3. 一元管理（黄） --}}
        <div class="col-4">
            <div id="card-feature-2" class="feature-card p-2 rounded h-100 d-flex flex-column justify-content-center align-items-center" onclick="switchFeature(2)" style="border: 2px solid #cbd5e1; background-color: #ffffff; cursor: pointer;">
                <div class="bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 38px; height: 38px;">
                    <i class="fa-solid fa-layer-group fs-6"></i>
                </div>
                <div class="fw-bold text-dark" style="font-size: 10px;">リモコン一元管理</div>
                <div class="text-muted mt-1 d-none d-md-block" style="font-size: 9px;">家中のリモコンをスマホへ</div>
                <span class="badge border mt-1 tap-hint" style="font-size: 8px;">タップ <i class="fa-solid fa-hand-pointer"></i></span>
            </div>
        </div>
    </div>

    {{-- 連動する活用シーン表示ボックス --}}
    <div class="p-3 bg-white border rounded-3 shadow-sm mb-3 position-relative">
        <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
            <span id="scene-tag" class="badge bg-primary px-2">AI音声操作の活用シーン</span>
            <span class="text-muted small ms-auto" style="font-size: 10px;"><i class="fa-regular fa-lightbulb text-warning me-1"></i>こんな時に大活躍！</span>
        </div>
        
        <div id="scene-content" class="scene-fade">
            {{-- JavaScriptで動的に挿入されます --}}
        </div>
    </div>

    <p class="text-center text-muted mb-0" style="font-size: 10px;">
        <i class="fa-solid fa-shield-halved me-1 text-secondary"></i> ESP32デバイスと低遅延MQTT通信による安全な遠隔制御
    </p>
</div>

{{-- アニメーションCSS --}}
<style>
    @keyframes pulseGlowBlue {
        0%, 100% { border-color: #0d6efd !important; box-shadow: 0 0 2px rgba(13, 110, 253, 0.3); background-color: #ffffff; }
        50% { border-color: #0d6efd !important; box-shadow: 0 0 14px 4px rgba(13, 110, 253, 0.65); background-color: #eff6ff; }
    }
    @keyframes pulseGlowGreen {
        0%, 100% { border-color: #198754 !important; box-shadow: 0 0 2px rgba(25, 135, 84, 0.3); background-color: #ffffff; }
        50% { border-color: #198754 !important; box-shadow: 0 0 14px 4px rgba(25, 135, 84, 0.65); background-color: #f0fdf4; }
    }
    @keyframes pulseGlowYellow {
        0%, 100% { border-color: #ffc107 !important; box-shadow: 0 0 2px rgba(255, 193, 7, 0.4); background-color: #ffffff; }
        50% { border-color: #ffc107 !important; box-shadow: 0 0 14px 4px rgba(255, 193, 7, 0.75); background-color: #fefce8; }
    }
    .scene-fade { animation: fadeInSlide 0.3s ease-out forwards; }
    @keyframes fadeInSlide { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    .chat-bubble { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px; font-size: 11px; color: #334155; line-height: 1.5; }
</style>

{{-- JS切り替え処理 --}}
<script>
    const featureScenes = [
        {
            title: "AI音声操作の活用シーン",
            badgeClass: "bg-primary",
            items: [
                { icon: "fa-kitchen-set text-primary", text: "「料理中で手が離せない！…『野菜のゆで時間何分だっけ？』も声だけで解決」" },
                { icon: "fa-bed text-primary", text: "「ベッドに入ってから電気の消し忘れてた… 布団から出ずにひとことで消灯！」" }
            ]
        },
        {
            title: "外出先から操作の活用シーン",
            badgeClass: "bg-success",
            items: [
                { icon: "fa-temperature-arrow-down text-success", text: "「猛暑の日に帰宅して家が涼しかったらな… 電車の中からエアコンを入れておけば涼しい部屋がお出迎え！」" },
                { icon: "fa-person-walking-arrow-right text-success", text: "「家を出てから『エアコン消したっけ？…』と不安になってもスマホで一瞬確認＆切断」" }
            ]
        },
        {
            title: "リモコン一元管理の活用シーン",
            badgeClass: "bg-warning text-dark",
            items: [
                { icon: "fa-tv text-warning", text: "「テレビ、エアコン、照明… 散らかりがちなテーブルのリモコンをスマホひとつにスッキリ集約」" },
                { icon: "fa-battery-quarter text-warning", text: "「使いたい時に限ってリモコンの電池切れ… スマホがあれば予備リモコンを探す手間も一切なし！」" }
            ]
        }
    ];

    function switchFeature(index) {
        const cardsConfig = [
            { id: 'card-feature-0', anim: 'pulseGlowBlue 1.8s infinite ease-in-out', color: '#0d6efd' },
            { id: 'card-feature-1', anim: 'pulseGlowGreen 1.8s infinite ease-in-out', color: '#198754' },
            { id: 'card-feature-2', anim: 'pulseGlowYellow 1.8s infinite ease-in-out', color: '#ffc107' }
        ];

        cardsConfig.forEach((item, i) => {
            const cardEl = document.getElementById(item.id);
            if (!cardEl) return;
            const hint = cardEl.querySelector('.tap-hint');

            if (i === index) {
                cardEl.style.animation = item.anim;
                cardEl.style.transform = 'scale(1.04)';
                cardEl.style.borderColor = item.color;
                if (hint) {
                    hint.innerHTML = '表示中 <i class="fa-solid fa-check"></i>';
                    hint.style.backgroundColor = item.color;
                    hint.style.color = i === 2 ? '#212529' : '#ffffff';
                    hint.style.borderColor = item.color;
                }
            } else {
                cardEl.style.animation = 'none';
                cardEl.style.transform = 'scale(1)';
                cardEl.style.borderColor = '#cbd5e1';
                cardEl.style.backgroundColor = '#ffffff';
                cardEl.style.boxShadow = 'none';
                if (hint) {
                    hint.innerHTML = 'タップ <i class="fa-solid fa-hand-pointer"></i>';
                    hint.style.backgroundColor = '#f8fafc';
                    hint.style.color = '#64748b';
                    hint.style.borderColor = '#cbd5e1';
                }
            }
        });

        const data = featureScenes[index];
        const sceneTag = document.getElementById('scene-tag');
        const sceneContent = document.getElementById('scene-content');
        if (!sceneTag || !sceneContent) return;

        sceneTag.className = `badge ${data.badgeClass} px-2`;
        sceneTag.textContent = data.title;

        let html = '<div class="d-flex flex-column gap-2">';
        data.items.forEach(item => {
            html += `
                <div class="chat-bubble d-flex align-items-center gap-2">
                    <i class="fa-solid ${item.icon} fs-6 flex-shrink-0"></i>
                    <div>${item.text}</div>
                </div>
            `;
        });
        html += '</div>';

        sceneContent.classList.remove('scene-fade');
        void sceneContent.offsetWidth;
        sceneContent.innerHTML = html;
        sceneContent.classList.add('scene-fade');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('scene-content')) {
            switchFeature(0);
        }
    });
</script>