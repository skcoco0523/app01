<h1 class="fs-4 fw-bold text-dark mb-2">スマートリモコンの初期設定・Wi-Fi接続とPINコード本登録の手順</h1>
<div class="d-flex align-items-center gap-3 text-muted small mb-4">
    <span><i class="fa-regular fa-calendar me-1"></i>2026年1月10日</span>
    <span><i class="fa-solid fa-tag me-1"></i>初期設定・デバイス連携</span>
</div>

<div class="mb-4 text-center">
    <img src="{{ asset('img/guide/guide_01_main.png') }}" alt="スマートリモコンのセットアップイメージ" class="img-fluid rounded border shadow-sm w-100" style="max-height: 350px; object-fit: cover;" onerror="this.onerror=null; this.src='https://placehold.co/800x350/e9ecef/6c757d?text=Image+Placeholder';">
</div>

<div class="article-body text-dark lh-lg small">
    <p class="lead fs-6 text-secondary mb-4">
        {{ config('app.name', 'Application') }}に対応したスマートリモコン端末のセットアップ手順です。<br>
        本デバイスはキャプティブポータル機能を搭載しているため、専用アプリ不要でスマホのブラウザから簡単にWi-Fi設定とWebアプリ連携が行えます。
    </p>

    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary"><i class="fa-solid fa-wifi me-2"></i>1. 設定用Wi-Fi（APモード）への接続</h2>
    <p>スマートリモコン端末の電源を入れると、自動的に設定用のアクセスポイント（APモード）が起動します。</p>
    <ol class="ps-3">
        <li>スマートフォンのWi-Fi設定画面を開き、表示されている設定用Wi-Fi（SSID）を選択して接続します。</li>
        <li>接続すると設定画面が自動的に立ち上がります。（自動で開かない場合は、ブラウザで <code>192.168.4.1</code> にアクセスしてください）</li>
    </ol>

    <div class="my-4 text-center">
        <img src="{{ asset('img/guide/guide_01_ap_connect.png') }}" alt="設定画面（ESP 設定メニュー）" class="img-fluid rounded border shadow-sm" style="max-width: 100%; max-height: 250px;" onerror="this.onerror=null; this.src='https://placehold.co/600x250/f8f9fa/adb5bd?text=ESP+Menu+Image';">
        <div class="text-muted mt-1" style="font-size: 11px;">▲ 接続後に表示される「ESP 設定メニュー」画面</div>
    </div>

    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary"><i class="fa-solid fa-sliders me-2"></i>2. Wi-Fi情報とデバイス名の設定</h2>
    <p>メニューから「<strong>デバイス設定</strong>」を選択し、端末に必要な情報を登録します。</p>
    <ul class="mb-3">
        <li><strong>Wi-Fi SSID / Password：</strong> ご自宅の2.4GHz帯Wi-Fiの情報を入力します。</li>
        <li><strong>デバイス名：</strong> 任意の識別名（例：ボット など）を入力します。</li>
    </ul>
    <p>入力完了後、「<strong>保存</strong>」ボタンを押すと端末に情報が保持されます。</p>

    <div class="my-4 text-center">
        <img src="{{ asset('img/guide/guide_01_device_setup.png') }}" alt="デバイス設定画面" class="img-fluid rounded border shadow-sm" style="max-width: 100%; max-height: 250px;" onerror="this.onerror=null; this.src='https://placehold.co/600x250/f8f9fa/adb5bd?text=Device+Setup+Image';">
        <div class="text-muted mt-1" style="font-size: 11px;">▲ Wi-Fi SSID・パスワードおよびデバイス名の入力フォーム</div>
    </div>

    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary"><i class="fa-solid fa-key me-2"></i>3. 仮登録とPINコードの確認</h2>
    <p>設定情報を保存すると仮登録状態となり、画面上に「PINコード」が発行・表示されます。</p>
    <div class="bg-light p-3 rounded mb-3 border text-center">
        <span class="badge bg-success mb-1">仮登録完了</span>
        <p class="mb-1 fw-bold text-dark">発行されたPINコードを控えてください</p>
        <p class="text-secondary small mb-0">このPINコードを使って、Webアプリケーション側で本登録を行います。</p>
    </div>

    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary"><i class="fa-solid fa-mobile-screen-button me-2"></i>4. Webアプリケーションでの本登録</h2>
    <ol class="ps-3">
        <li>スマートフォンをご自宅の通常のWi-Fiに繋ぎ直し、{{ config('app.name', 'Application') }}にログインします。</li>
        <li>「スマートリモコン」メニュー内の「新規デバイス登録」画面を開きます。</li>
        <li>先ほど設定した「<strong>デバイス名</strong>」と「<strong>PINコード</strong>」を入力してアクティベートを実行します。</li>
    </ol>
</div>
