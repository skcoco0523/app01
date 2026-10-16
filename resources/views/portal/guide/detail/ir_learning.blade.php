<h1 class="fs-4 fw-bold text-dark mb-2">赤外線リモコン信号の学習とボタン登録のベストプラクティス</h1>
<div class="d-flex align-items-center gap-3 text-muted small mb-4">
    <span><i class="fa-regular fa-calendar me-1"></i>2026年1月10日</span>
    <span><i class="fa-solid fa-tag me-1"></i>機能ガイド・赤外線</span>
</div>

<div class="mb-4 text-center">
    <img src="{{ asset('img/guide/guide_03_main.png') }}" alt="赤外線学習のイメージ" class="img-fluid rounded border shadow-sm w-100" style="max-height: 350px; object-fit: cover;" onerror="this.onerror=null; this.src='https://placehold.co/800x350/e9ecef/6c757d?text=IR+Learning+Image';">
</div>

<div class="article-body text-dark lh-lg small">
    <p class="lead fs-6 text-secondary mb-4">スマートリモコンを長期間安定して利用するために、初期設定時に行っておくべき「通信の安定化」と「利便性の向上」に繋がるテクニックを解説します。</p>
    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary"><i class="fa-solid fa-signal me-2"></i>1. 赤外線信号の学習を成功させるコツ</h2>
    <p>家電リモコンの多くは38kHzのキャリア周波数帯を利用しています。<br>学習時は、既存リモコンの送信部をスマートリモコンの受光部に**3〜5cm程度**近づけた状態でボタンを押してください。</p>
    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary"><i class="fa-solid fa-sun me-2"></i>2. 環境光（ノイズ）の遮断</h2>
    <p>直射日光やインバーター式蛍光灯の強い光は赤外線波長と干渉し、学習エラーの原因となります。<br>直射日光を避け、部屋の照明を少し落とした状態で行うと認識率が大幅に向上します。</p>
    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary"><i class="fa-solid fa-microchip me-2"></i>3. ESP32の特性を活かした配置</h2>
    <p>本システムで使用しているESP32は、内蔵Wi-Fiアンテナの向きによって通信強度が変化します。<br>できるだけ部屋の中央付近、あるいは操作したい家電が見通せる、少し高い位置（棚の上など）に配置するのがベストです。</p>
</div>
