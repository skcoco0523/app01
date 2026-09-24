@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width: 800px;">
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            {{-- 一覧へ戻る --}}
            <div class="mb-3">
                <a href="{{ route('guide.index') }}" class="small text-decoration-none text-primary">
                    <i class="fa-solid fa-chevron-left"></i> ガイド一覧へ戻る
                </a>
            </div>

            @if($id == 2)
                {{-- ======================================================== --}}
                {{-- 記事2：PWAアプリのインストール方法 --}}
                {{-- ======================================================== --}}
                <h1 class="fs-4 fw-bold text-dark mb-2">{{ config('app.name', 'Application') }}をPWAアプリとしてスマホに追加・利用する方法</h1>
                <div class="d-flex align-items-center gap-3 text-muted small mb-4">
                    <span><i class="fa-regular fa-calendar me-1"></i>2026年1月10日</span>
                    <span><i class="fa-solid fa-tag me-1"></i>PWA・アプリ設定</span>
                </div>

                {{-- アイキャッチ画像 --}}
                <div class="mb-4 text-center">
                    <img src="{{ asset('img/guide/guide_02_main.png') }}" 
                         alt="PWAインストールのイメージ" 
                         class="img-fluid rounded border shadow-sm w-100" 
                         style="max-height: 350px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='https://placehold.co/800x350/e9ecef/6c757d?text=PWA+Install+Image';">
                </div>

                <div class="article-body text-dark lh-lg small">
                    <p class="lead fs-6 text-secondary mb-4">
                        {{ config('app.name', 'Application') }}は**PWA（Progressive Web Apps）**に対応しています。<br>
                        App StoreやGoogle Playストアを経由せずに、スマホのホーム画面にアプリとして追加でき、全画面表示や高速な起動が可能になります。
                    </p>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-brands fa-apple me-2"></i>1. iOS（iPhone / iPad）での追加手順
                    </h2>
                    <p>標準ブラウザ「<strong>Safari</strong>」を開いて設定を行います。</p>
                    <ol class="ps-3">
                        <li>Safariで{{ config('app.name', 'Application') }}にアクセスします。<br>（※Chrome等の別ブラウザでは追加ボタンが表示されない場合があります）</li>
                        <li>画面下部の中央にある「<strong>共有ボタン</strong>（<i class="fa-solid fa-share-from-square text-primary"></i>）」をタップします。</li>
                        <li>メニューを下にスクロールし、「<strong>ホーム画面に追加</strong>」を選択します。</li>
                        <li>右上の「<strong>追加</strong>」をタップすると、ホーム画面にアイコンが配置されます。</li>
                    </ol>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-brands fa-android me-2"></i>2. Android（Chrome）での追加手順
                    </h2>
                    <p>「<strong>Google Chrome</strong>」ブラウザから簡単にインストールできます。</p>
                    <ol class="ps-3">
                        <li>Google Chromeで{{ config('app.name', 'Application') }}を開きます。</li>
                        <li>画面右上のメニューボタン（縦の3点リーダー）をタップします。</li>
                        <li>「<strong>アプリをインストール</strong>」または「<strong>ホーム画面に追加</strong>」を選択します。</li>
                        <li>画面の指示に従って「インストール」をタップします。</li>
                    </ol>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-bell me-2"></i>3. プッシュ通知とPWA機能のメリット
                    </h2>
                    <p>ホーム画面に追加して利用することで、以下のメリットがあります。</p>
                    <ul>
                        <li><strong>高速起動：</strong><br>
                            ブラウザのアドレスバーが表示されず、アプリ同様に全画面で快適に操作できます。</li>
                        <li><strong>WebPush通知の受信：</strong><br>
                            スマートリモコンの動作結果やフレンドからのメッセージ通知をリアルタイムで受け取ることができます。</li>
                    </ul>
                </div>

            @elseif($id == 3)
                {{-- ======================================================== --}}
                {{-- 記事3：赤外線学習ガイド --}}
                {{-- ======================================================== --}}
                <h1 class="fs-4 fw-bold text-dark mb-2">赤外線リモコン信号の学習とボタン登録のベストプラクティス</h1>
                <div class="d-flex align-items-center gap-3 text-muted small mb-4">
                    <span><i class="fa-regular fa-calendar me-1"></i>2026年1月10日</span>
                    <span><i class="fa-solid fa-tag me-1"></i>機能ガイド・赤外線</span>
                </div>

                {{-- アイキャッチ画像 --}}
                <div class="mb-4 text-center">
                    <img src="{{ asset('img/guide/guide_03_main.png') }}" 
                         alt="赤外線学習のイメージ" 
                         class="img-fluid rounded border shadow-sm w-100" 
                         style="max-height: 350px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='https://placehold.co/800x350/e9ecef/6c757d?text=IR+Learning+Image';">
                </div>

                <div class="article-body text-dark lh-lg small">
                    <p class="lead fs-6 text-secondary mb-4">
                        スマートリモコンを長期間安定して利用するために、初期設定時に行っておくべき「通信の安定化」と「利便性の向上」に繋がるテクニックを解説します。
                    </p>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-signal me-2"></i>1. 赤外線信号の学習を成功させるコツ
                    </h2>
                    <p>
                        家電リモコンの多くは38kHzのキャリア周波数帯を利用しています。<br>
                        学習時は、既存リモコンの送信部をスマートリモコンの受光部に**3〜5cm程度**近づけた状態でボタンを押してください。<br>
                        離れすぎていると信号を正しく解析できず、近すぎても赤外線が飽和してエラーになる場合があります。
                    </p>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-sun me-2"></i>2. 環境光（ノイズ）の遮断
                    </h2>
                    <p>
                        直射日光やインバーター式蛍光灯の強い光は赤外線波長と干渉し、学習エラーの原因となります。<br>
                        学習操作を行う際は、直射日光を避け、部屋の照明を少し落とした状態で行うと認識率が大幅に向上します。
                    </p>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-microchip me-2"></i>3. ESP32の特性を活かした配置
                    </h2>
                    <p>
                        本システムで使用しているESP32は、内蔵Wi-Fiアンテナの向きによって通信強度が変化します。<br>
                        スマートリモコンを金属製の棚の中に置いたり、家電の裏側に隠したりすると、Wi-Fi接続が不安定になるだけでなく、赤外線の反射効率も悪くなります。<br>
                        できるだけ部屋の中央付近、あるいは操作したい家電が見通せる、少し高い位置（棚の上など）に配置するのがベストです。
                    </p>
                </div>

            @elseif($id == 4)
                {{-- ======================================================== --}}
                {{-- 記事4：フレンド共有機能 --}}
                {{-- ======================================================== --}}
                <h1 class="fs-4 fw-bold text-dark mb-2">フレンド機能を使ったメモやスマートリモコンの共有・権限設定</h1>
                <div class="d-flex align-items-center gap-3 text-muted small mb-4">
                    <span><i class="fa-regular fa-calendar me-1"></i>2026年1月10日</span>
                    <span><i class="fa-solid fa-tag me-1"></i>便利機能・共有</span>
                </div>

                {{-- アイキャッチ画像 --}}
                <div class="mb-4 text-center">
                    <img src="{{ asset('img/guide/guide_04_main.png') }}" 
                         alt="フレンド共有のイメージ" 
                         class="img-fluid rounded border shadow-sm w-100" 
                         style="max-height: 350px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='https://placehold.co/800x350/e9ecef/6c757d?text=Friend+Share+Image';">
                </div>

                <div class="article-body text-dark lh-lg small">
                    <p class="lead fs-6 text-secondary mb-4">
                        {{ config('app.name', 'Application') }}では、家族や友人とフレンド連携を行うことで、作成したメモや登録したスマートリモコンを安全に共同利用できます。
                    </p>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-user-plus me-2"></i>1. ユーザーID（フレンドコード）での申請
                    </h2>
                    <p>
                        プロフィール画面に表示されるユーザーIDを相手に共有し、フレンド画面から検索・申請を送ります。<br>
                        相手が承認すると相互にフレンド状態となり、各種共有機能が利用可能になります。
                    </p>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-share-nodes me-2"></i>2. リモコン・メモの共有と権限変更
                    </h2>
                    <p>
                        各アイテムの詳細画面にある「共有」ボタンから、共有したいフレンドを選択します。<br>
                        閲覧のみを許可するか、編集・家電の操作権限まで許可するかをいつでもワンタップで切り替えられます。
                    </p>
                </div>

            @elseif($id == 5)
                {{-- ======================================================== --}}
                {{-- 記事5：トラブルシューティング --}}
                {{-- ======================================================== --}}
                <h1 class="fs-4 fw-bold text-dark mb-2">家電が反応しない・オフライン表示になる場合の対処法</h1>
                <div class="d-flex align-items-center gap-3 text-muted small mb-4">
                    <span><i class="fa-regular fa-calendar me-1"></i>2026年1月10日</span>
                    <span><i class="fa-solid fa-tag me-1"></i>トラブルシューティング</span>
                </div>

                {{-- アイキャッチ画像 --}}
                <div class="mb-4 text-center">
                    <img src="{{ asset('img/guide/guide_05_main.png') }}" 
                         alt="トラブルシューティングのイメージ" 
                         class="img-fluid rounded border shadow-sm w-100" 
                         style="max-height: 350px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='https://placehold.co/800x350/e9ecef/6c757d?text=Trouble+Shoot+Image';">
                </div>

                <div class="article-body text-dark lh-lg small">
                    <p class="lead fs-6 text-secondary mb-4">
                        スマートリモコンがオフライン表示になった際や、操作ボタンを押しても家電が反応しない場合のチェックポイントです。
                    </p>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-network-wired me-2"></i>1. Wi-Fi通信とIPアドレス固定の推奨
                    </h2>
                    <p>
                        ルーターの再起動でIPアドレスが変更され、一時的に切断されることがあります。<br>
                        ご自宅のWi-Fiルーター管理画面から、スマートリモコンのMACアドレスに対してIPアドレスを固定割り当て（DHCPバインド）すると通信が非常に安定します。
                    </p>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-eye-slash me-2"></i>2. 物理的な遮蔽物と赤外線照射角度
                    </h2>
                    <p>
                        赤外線は壁や天井に反射して届きますが、家具や棚などの遮蔽物があると電波強度が低下します。<br>
                        家電の受光部が見通せる位置にスマートリモコン端末を配置し直してください。
                    </p>
                </div>

            @else
                {{-- ======================================================== --}}
                {{-- 記事1（デフォルト）：スマートリモコン初期設定 --}}
                {{-- ======================================================== --}}
                <h1 class="fs-4 fw-bold text-dark mb-2">スマートリモコンの初期設定・Wi-Fi接続とPINコード本登録の手順</h1>
                <div class="d-flex align-items-center gap-3 text-muted small mb-4">
                    <span><i class="fa-regular fa-calendar me-1"></i>2026年1月10日</span>
                    <span><i class="fa-solid fa-tag me-1"></i>初期設定・デバイス連携</span>
                </div>

                {{-- アイキャッチ画像 --}}
                <div class="mb-4 text-center">
                    <img src="{{ asset('img/guide/guide_01_main.png') }}" 
                         alt="スマートリモコンのセットアップイメージ" 
                         class="img-fluid rounded border shadow-sm w-100" 
                         style="max-height: 350px; object-fit: cover;"
                         onerror="this.onerror=null; this.src='https://placehold.co/800x350/e9ecef/6c757d?text=Image+Placeholder';">
                </div>

                {{-- 記事本文 --}}
                <div class="article-body text-dark lh-lg small">
                    <p class="lead fs-6 text-secondary mb-4">
                        {{ config('app.name', 'Application') }}に対応したスマートリモコン端末のセットアップ手順です。<br>
                        本デバイスはキャプティブポータル機能を搭載しているため、専用アプリ不要でスマホのブラウザから簡単にWi-Fi設定とWebアプリ連携が行えます。
                    </p>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-wifi me-2"></i>1. 設定用Wi-Fi（APモード）への接続
                    </h2>
                    <p>
                        スマートリモコン端末の電源を入れると、自動的に設定用のアクセスポイント（APモード）が起動します。
                    </p>
                    <ol class="ps-3">
                        <li>スマートフォンのWi-Fi設定画面を開き、<br>表示されている設定用Wi-Fi（SSID）を選択して接続します。</li>
                        <li>接続すると設定画面が自動的に立ち上がります。<br>（自動で開かない場合は、ブラウザで <code>192.168.4.1</code> にアクセスしてください）</li>
                    </ol>

                    {{-- 本文差し込み画像 1 --}}
                    <div class="my-4 text-center">
                        <img src="{{ asset('img/guide/guide_01_ap_connect.png') }}" 
                             alt="設定画面（ESP 設定メニュー）" 
                             class="img-fluid rounded border shadow-sm" 
                             style="max-width: 100%; max-height: 250px;"
                             onerror="this.onerror=null; this.src='https://placehold.co/600x250/f8f9fa/adb5bd?text=ESP+Menu+Image';">
                        <div class="text-muted mt-1" style="font-size: 11px;">▲ 接続後に表示される「ESP 設定メニュー」画面</div>
                    </div>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-sliders me-2"></i>2. Wi-Fi情報とデバイス名の設定
                    </h2>
                    <p>メニューから「<strong>デバイス設定</strong>」を選択し、端末に必要な情報を登録します。</p>
                    <ul class="mb-3">
                        <li><strong>Wi-Fi SSID：</strong><br>
                            ご自宅の2.4GHz帯Wi-FiのSSIDを入力します。</li>
                        <li><strong>Wi-Fi Password：</strong><br>
                            Wi-Fiのパスワードを入力します。</li>
                        <li><strong>デバイス名：</strong><br>
                            任意の識別名（例：ボット など）を入力します。</li>
                    </ul>
                    <p>入力完了後、「<strong>保存</strong>」ボタンを押すと端末に情報が保持されます。</p>

                    {{-- 本文差し込み画像 2 --}}
                    <div class="my-4 text-center">
                        <img src="{{ asset('img/guide/guide_01_device_setup.png') }}" 
                             alt="デバイス設定画面" 
                             class="img-fluid rounded border shadow-sm" 
                             style="max-width: 100%; max-height: 250px;"
                             onerror="this.onerror=null; this.src='https://placehold.co/600x250/f8f9fa/adb5bd?text=Device+Setup+Image';">
                        <div class="text-muted mt-1" style="font-size: 11px;">▲ Wi-Fi SSID・パスワードおよびデバイス名の入力フォーム</div>
                    </div>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-key me-2"></i>3. 仮登録とPINコードの確認
                    </h2>
                    <p>
                        設定情報を保存すると仮登録状態となり、画面上に「PINコード」が発行・表示されます。
                    </p>
                    <div class="bg-light p-3 rounded mb-3 border text-center">
                        <span class="badge bg-success mb-1">仮登録完了</span>
                        <p class="mb-1 fw-bold text-dark">発行されたPINコードを控えてください</p>
                        <p class="text-secondary small mb-0">このPINコードを使って、Webアプリケーション（{{ config('app.name', 'Application') }}）側で本登録を行います。</p>
                    </div>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-mobile-screen-button me-2"></i>4. Webアプリケーションでの本登録
                    </h2>
                    <p>控えたPINコードを使って、当Webサイト上でアクティベーションを完了させます。</p>
                    <ol class="ps-3">
                        <li>スマートフォンをご自宅の通常のWi-Fiに繋ぎ直し、{{ config('app.name', 'Application') }}にログインします。</li>
                        <li>「スマートリモコン」メニュー内の「新規デバイス登録」画面を開きます。</li>
                        <li>先ほど設定した「<strong>デバイス名</strong>」と「<strong>PINコード</strong>」を入力してアクティベートを実行します。</li>
                        <li>本登録が完了すると、Webサイト上から遠隔での家電操作が可能になります。</li>
                    </ol>

                    <h2 class="fs-6 fw-bold border-bottom pb-2 mt-4 text-primary">
                        <i class="fa-solid fa-circle-info me-2"></i>5. 便利な確認機能とトラブルシューティング
                    </h2>
                    <p>設定ポータルメニュー（`192.168.4.1`）では、以下の便利機能や確認画面が利用できます。</p>
                    <ul>
                        <li><strong>ネットワーク情報（/networkInfo）：</strong><br>
                            ご自宅のルーターから割り当てられた内部IPアドレスや電波強度（RSSI）を確認できます。</li>
                        <li><strong>システム情報（/systemInfo）：</strong><br>
                            MACアドレスやCPU周波数、ヒープメモリ残量を確認できます。</li>
                        <li><strong>デバイス再起動（/reboot）：</strong><br>
                            動作が不安定な場合、端末を再起動できます。</li>
                    </ul>
                @endif

                {{-- 共通サポート案内 --}}
                <div class="mt-5 p-3 bg-white border rounded shadow-sm text-center">
                    <p class="mb-2 fw-bold text-dark">設定についてご不明な点がある場合</p>
                    <p class="text-secondary small mb-3">操作や設定が上手くいかない場合は、お気軽にお問い合わせください。</p>
                    <a href="{{ route('contact.index') }}" class="btn btn-outline-primary btn-sm px-4 fw-bold">
                        <i class="fa-regular fa-envelope me-1"></i>お問い合わせフォームへ
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection