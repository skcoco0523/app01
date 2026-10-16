{{-- ライフシアター 3大特徴・活用シーン共通コンポーネント --}}
<div class="py-3 px-2 px-md-3">
    <div class="d-flex align-items-center gap-2 mb-2">
        <span class="badge px-2 py-1" style="font-size: 10px; background-color: #6f42c1; color: #fff;">Memory & Movie Platform</span>
    </div>
    
    <h2 class="fs-4 fw-bold text-dark mb-2">大切な思い出を、まるで映画のように。</h2>
    <p class="text-secondary small lh-lg mb-3">
        スマホに眠っている日常の写真や旅の動画を、感動的なスライドショーとシアター演出でよみがえらせるフォト・ムービープラットフォームです。
    </p>
    
    {{-- 3大特徴カード --}}
    <div class="row text-center g-2 mb-3">
        {{-- 1. シアター再生（紫） --}}
        <div class="col-4">
            <div id="card-lt-feature-0" class="lt-feature-card p-2 rounded h-100 d-flex flex-column justify-content-center align-items-center" onclick="switchLifeFeature(0)" style="border: 2px solid #cbd5e1; background-color: #ffffff; cursor: pointer;">
                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 38px; height: 38px; background-color: #f3e8ff; color: #6f42c1;">
                    <i class="fa-solid fa-film fs-6"></i>
                </div>
                <div class="fw-bold text-dark" style="font-size: 10px;">シアター上映</div>
                <div class="text-muted mt-1 d-none d-md-block" style="font-size: 9px;">自動スライド再生</div>
                <span class="badge border mt-1 lt-tap-hint" style="font-size: 8px;">表示中 <i class="fa-solid fa-check"></i></span>
            </div>
        </div>

        {{-- 2. 大切な人と共有（ピンク） --}}
        <div class="col-4">
            <div id="card-lt-feature-1" class="lt-feature-card p-2 rounded h-100 d-flex flex-column justify-content-center align-items-center" onclick="switchLifeFeature(1)" style="border: 2px solid #cbd5e1; background-color: #ffffff; cursor: pointer;">
                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 38px; height: 38px; background-color: #fce7f3; color: #d63384;">
                    <i class="fa-solid fa-users-viewfinder fs-6"></i>
                </div>
                <div class="fw-bold text-dark" style="font-size: 10px;">限定シェア</div>
                <div class="text-muted mt-1 d-none d-md-block" style="font-size: 9px;">家族や友人と共有</div>
                <span class="badge border mt-1 lt-tap-hint" style="font-size: 8px;">タップ <i class="fa-solid fa-hand-pointer"></i></span>
            </div>
        </div>

        {{-- 3. プレミアム演出（ゴールド/オレンジ） --}}
        <div class="col-4">
            <div id="card-lt-feature-2" class="lt-feature-card p-2 rounded h-100 d-flex flex-column justify-content-center align-items-center" onclick="switchLifeFeature(2)" style="border: 2px solid #cbd5e1; background-color: #ffffff; cursor: pointer;">
                <div class="rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 38px; height: 38px; background-color: #fff7ed; color: #ea580c;">
                    <i class="fa-solid fa-crown fs-6"></i>
                </div>
                <div class="fw-bold text-dark" style="font-size: 10px;">高品質エフェクト</div>
                <div class="text-muted mt-1 d-none d-md-block" style="font-size: 9px;">特別演出・大画面</div>
                <span class="badge border mt-1 lt-tap-hint" style="font-size: 8px;">タップ <i class="fa-solid fa-hand-pointer"></i></span>
            </div>
        </div>
    </div>

    {{-- 連動する活用シーン表示ボックス --}}
    <div class="p-3 bg-white border rounded-3 shadow-sm mb-3 position-relative">
        <div class="d-flex align-items-center gap-2 mb-2 pb-1 border-bottom">
            <span id="lt-scene-tag" class="badge px-2" style="background-color: #6f42c1; color: #fff;">シアター上映の活用シーン</span>
            <span class="text-muted small ms-auto" style="font-size: 10px;"><i class="fa-regular fa-lightbulb text-warning me-1"></i>こんな楽しみ方！</span>
        </div>
        
        <div id="lt-scene-content" class="lt-scene-fade">
            {{-- JavaScriptで動的に挿入されます --}}
        </div>
    </div>

    <p class="text-center text-muted mb-0" style="font-size: 10px;">
        <i class="fa-solid fa-lock me-1 text-secondary"></i> クラウド上での安全な暗号化ストレージ管理と高速ストリーミング
    </p>
</div>

{{-- アニメーションCSS --}}
<style>
    @keyframes pulseGlowPurple {
        0%, 100% { border-color: #6f42c1 !important; box-shadow: 0 0 2px rgba(111, 66, 193, 0.3); background-color: #ffffff; }
        50% { border-color: #6f42c1 !important; box-shadow: 0 0 14px 4px rgba(111, 66, 193, 0.65); background-color: #faf5ff; }
    }
    @keyframes pulseGlowPink {
        0%, 100% { border-color: #d63384 !important; box-shadow: 0 0 2px rgba(214, 51, 132, 0.3); background-color: #ffffff; }
        50% { border-color: #d63384 !important; box-shadow: 0 0 14px 4px rgba(214, 51, 132, 0.65); background-color: #fdf2f8; }
    }
    @keyframes pulseGlowAmber {
        0%, 100% { border-color: #ea580c !important; box-shadow: 0 0 2px rgba(234, 88, 12, 0.3); background-color: #ffffff; }
        50% { border-color: #ea580c !important; box-shadow: 0 0 14px 4px rgba(234, 88, 12, 0.65); background-color: #fffbeb; }
    }
    .lt-scene-fade { animation: ltFadeInSlide 0.3s ease-out forwards; }
    @keyframes ltFadeInSlide { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    .lt-chat-bubble { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 8px 12px; font-size: 11px; color: #334155; line-height: 1.5; }
</style>

{{-- JS切り替え処理 --}}
<script>
    const lifeFeatureScenes = [
        {
            title: "シアター上映の活用シーン",
            color: "#6f42c1",
            items: [
                { icon: "fa-champagne-glasses", color: "#6f42c1", text: "「記念日や誕生日に… 家族や友人の思い出写真をBGM付きスライドショーでドラマチックに上映！」" },
                { icon: "fa-plane-departure", color: "#6f42c1", text: "「散らかりがちな旅行の写真・動画を整理不要でひとまとめ。自動で流れる旅のアルバムに」" }
            ]
        },
        {
            title: "限定シェアの活用シーン",
            color: "#d63384",
            items: [
                { icon: "fa-house-chimney-user", color: "#d63384", text: "「離れて暮らす祖父母や両親へ、子どもの成長記録やイベント動画を安全・手軽にプライベート共有」" },
                { icon: "fa-comments", color: "#d63384", text: "「SNSには載せたくない内輪の思い出も、合言葉やフレンド限定公開で仲間うちだけで安心して楽しめる」" }
            ]
        },
        {
            title: "高品質エフェクトの活用シーン",
            color: "#ea580c",
            items: [
                { icon: "fa-tv", color: "#ea580c", text: "「リビングの大画面テレビやプロジェクターに映し出して、まるで映画館のような大迫力の鑑賞会！」" },
                { icon: "fa-wand-magic-sparkles", color: "#ea580c", text: "「プレミアム限定の美しいトランジションや映画風フィルターで、普段の写真がワンランク上の作品へ」" }
            ]
        }
    ];

    function switchLifeFeature(index) {
        const cardsConfig = [
            { id: 'card-lt-feature-0', anim: 'pulseGlowPurple 1.8s infinite ease-in-out', color: '#6f42c1' },
            { id: 'card-lt-feature-1', anim: 'pulseGlowPink 1.8s infinite ease-in-out', color: '#d63384' },
            { id: 'card-lt-feature-2', anim: 'pulseGlowAmber 1.8s infinite ease-in-out', color: '#ea580c' }
        ];

        cardsConfig.forEach((item, i) => {
            const cardEl = document.getElementById(item.id);
            if (!cardEl) return;
            const hint = cardEl.querySelector('.lt-tap-hint');

            if (i === index) {
                cardEl.style.animation = item.anim;
                cardEl.style.transform = 'scale(1.04)';
                cardEl.style.borderColor = item.color;
                if (hint) {
                    hint.innerHTML = '表示中 <i class="fa-solid fa-check"></i>';
                    hint.style.backgroundColor = item.color;
                    hint.style.color = '#ffffff';
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

        const data = lifeFeatureScenes[index];
        const sceneTag = document.getElementById('lt-scene-tag');
        const sceneContent = document.getElementById('lt-scene-content');
        if (!sceneTag || !sceneContent) return;

        sceneTag.style.backgroundColor = data.color;
        sceneTag.textContent = data.title;

        let html = '<div class="d-flex flex-column gap-2">';
        data.items.forEach(item => {
            html += `
                <div class="lt-chat-bubble d-flex align-items-center gap-2">
                    <i class="fa-solid ${item.icon} fs-6 flex-shrink-0" style="color: ${item.color};"></i>
                    <div>${item.text}</div>
                </div>
            `;
        });
        html += '</div>';

        sceneContent.classList.remove('lt-scene-fade');
        void sceneContent.offsetWidth;
        sceneContent.innerHTML = html;
        sceneContent.classList.add('lt-scene-fade');
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('lt-scene-content')) {
            switchLifeFeature(0);
        }
    });
</script>