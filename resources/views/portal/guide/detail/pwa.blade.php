<h1 class="fs-4 fw-bold text-dark mb-2">{{ config('app.name', 'Application') }}をPWAアプリとしてスマホに追加・利用する方法</h1>
<div class="d-flex align-items-center gap-3 text-muted small mb-4">
    <span><i class="fa-regular fa-calendar me-1"></i>2026年1月10日</span>
    <span><i class="fa-solid fa-tag me-1"></i>PWA・アプリ設定</span>
</div>

<div class="mb-4 text-center">
    <img src="{{ asset('img/guide/guide_02_main.png') }}" alt="PWAインストールのイメージ" class="img-fluid rounded border shadow-sm w-100" style="max-height: 350px; object-fit: cover;" onerror="this.onerror=null; this.src='https://placehold.co/800x350/e9ecef/6c757d?text=PWA+Install+Image';">
</div>

<div class="article-body text-dark lh-lg small">
    <p class="lead fs-6 text-secondary mb-4">
        {{ config('app.name', 'Application') }}は**PWA（Progressive Web Apps）**に対応しています。<br>
        App StoreやGoogle Playストアを経由せずに、スマホのホーム画面にアプリとして追加でき、全画面表示や高速な起動が可能になります。
    </p>

    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary"><i class="fa-brands fa-apple me-2"></i>1. iOS（iPhone / iPad）での追加手順</h2>
    <p>標準ブラウザ「<strong>Safari</strong>」を開いて設定を行います。</p>
    <ol class="ps-3">
        <li>Safariで{{ config('app.name', 'Application') }}にアクセスします。</li>
        <li>画面下部の中央にある「<strong>共有ボタン</strong>（<i class="fa-solid fa-share-from-square text-primary"></i>）」をタップします。</li>
        <li>メニューを下にスクロールし、「<strong>ホーム画面に追加</strong>」を選択します。</li>
        <li>右上の「<strong>追加</strong>」をタップすると、ホーム画面にアイコンが配置されます。</li>
    </ol>

    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary"><i class="fa-brands fa-android me-2"></i>2. Android（Chrome）での追加手順</h2>
    <p>「<strong>Google Chrome</strong>」ブラウザから簡単にインストールできます。</p>
    <ol class="ps-3">
        <li>Google Chromeで{{ config('app.name', 'Application') }}を開きます。</li>
        <li>画面右上のメニューボタン（縦の3点リーダー）をタップします。</li>
        <li>「<strong>アプリをインストール</strong>」または「<strong>ホーム画面に追加</strong>」を選択します。</li>
        <li>画面の指示に従って「インストール」をタップします。</li>
    </ol>

    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary"><i class="fa-solid fa-bell me-2"></i>3. プッシュ通知とPWA機能のメリット</h2>
    <ul>
        <li><strong>高速起動：</strong>ブラウザのアドレスバーが表示されず、アプリ同様に全画面で快適に操作できます。</li>
        <li><strong>WebPush通知の受信：</strong>スマートリモコンの動作結果やフレンドからのメッセージ通知をリアルタイムで受け取ることができます。</li>
    </ul>
</div>
