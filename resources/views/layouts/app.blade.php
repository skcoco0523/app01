
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <!-- <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#000000"> <!-- テーマカラー -->
    <!-- アイコン設定（オプション） -->
    <!-- <link rel="icon" href="/icons/icon-192x192.png" sizes="192x192" type="image/png"> -->
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'その他') }}</title>
    <link rel="icon" href="{{ asset('img/icon/home_icon_16_16.png') }}" sizes="16x16" type="image/png">
    <link rel="icon" href="{{ asset('img/icon/home_icon_32_32.png') }}" sizes="32x32" type="image/png">
    <link rel="icon" href="{{ asset('img/icon/home_icon_48_48.png') }}" sizes="48x48" type="image/png">
    
    <meta name="description" content="簡易アプリのまとめ">
    <meta name="keywords" content="スマートリモコン, 共有memo, ルーレット">
    
    <?//SNSで表示される際の説明?>
    <meta property="og:url" content="https://skcoco.com/app01">
    <meta property="og:title" content="{{ config('app.name', 'その他') }}">
    <meta property="og:description" content="フレンド間でお気に入りに登録した曲などを共有するアプリケーション">
    <meta property="og:type" content="website">
    
    @php
        $homeUrl = route('home');
        $logoUrl = asset('img/icon/home_icon_192_192.png');
    @endphp
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "その他",
        "url": "{{ $homeUrl }}",
        "logo": {{ $logoUrl }}",
        "sameAs": [
            //"https://www.facebook.com/yourprofile",
            //"https://twitter.com/yourprofile"
        ]
    }
    </script>



    <!-- jQueryの読み込み -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


    <script>
        // ベースURLを定義
        //const baseUrl = "{{ url('/app01') }}";
        //JS用ルーティングの定義
        const loginUrl                      = "{{ route('login') }}";
        const checkDevicesUrl               = "{{ route('devices.check') }}";
        
        // URLベースを定義
        const remoteUrlBase                 = "{{ url('/smart-remote') }}";
        const noteUrlBase                   = "{{ url('/note') }}";
        const friendUrlBase                 = "{{ url('/friend') }}";
        const iotDeviceUrlBase              = "{{ url('/iotdevice') }}";
        
        // APIエンドポイントのURLを定義
        const api_login                     = `{{ url('/api/login') }}`;
        const getAdvertisementUrl           = `{{ url('/api/adv/get') }}`;
        const AdvertisementClickUrl         = `{{ url('/api/adv/click') }}`;
        const getVirtualRemoteBladeUrl      = `{{ url('/api/remote-blade/get') }}`;
        const getIotDevicesUrl              = `{{ url('/api/iot_devices/get') }}`;
        const getFriendlistUrl              = `{{ url('/api/friendlist/get') }}`;
        const shareNoteWithFriendUrl        = `{{ url('/api/note/share') }}`;
        const unshareNoteFromFriendUrl      = `{{ url('/api/note/unshare') }}`;
        const enableEditForSharedNoteUrl    = `{{ url('/api/note/enable-edit') }}`;
        const disableEditForSharedNoteUrl   = `{{ url('/api/note/disable-edit') }}`;

        //パスを定義
        const publicDir = "{{ asset('/sounds') }}";  // public/sounds ディレクトリのパスを格納
        
        //configからJSで使用する値を取得
        window.Laravel = {
            user_id: '{{Auth::id() }}',
            vapidPublicKey: '{{ config('webpush.vapid.public_key') }}',
            
            loginSuccess: @json(session('login_success', false)),   //セッションからログイン成功フラグを取得
        };
    </script>
    
    <!-- resources/js/app.js　で一括管理
    <script src="{{ asset('js/notification.js') }}"></script>
    <script src="{{ asset('js/favorite_change.js') }}"></script>
    <script src="{{ asset('js/custom_category_change.js') }}"></script>
    <script src="{{ asset('js/tab.js') }}"></script>
    <script src="{{ asset('js/search_history.js') }}"></script>
    -->
    <!-- Font Awesome CDN アイコン　　　https://fontawesome.com/　　　-->
    <script src="https://kit.fontawesome.com/46a805b165.js" crossorigin="anonymous"></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- 追加　resources/css,jsで管理　viteでビルドしてアップロード -->
    <!--<link rel="stylesheet" href="{{ asset('/css/style.css') }}">-->
    
    <!-- 音声分析に edge-impulseを使用する -->
    <script>
        var Module = {
            locateFile: function(path, prefix) {
                if (path.endsWith('.wasm')) {
                    return "{{ asset('edge-impulse-standalone.wasm') }}?v={{ filemtime(public_path('edge-impulse-standalone.wasm')) }}";
                }
                return prefix + path;
            },
            onRuntimeInitialized: function() {
                console.log('Edge Impulse WASM initialized');
            }
        };
    </script>
    <script src="{{ asset('js/edge-impulse-standalone.js') }}?v={{ filemtime(public_path('js/edge-impulse-standalone.js')) }}"></script>
    <script src="{{ asset('js/run-impulse.js') }}?v={{ filemtime(public_path('js/run-impulse.js')) }}"></script>
    <!--デバッグ用-->
    <link rel="stylesheet" href="{{ asset('css/debug.css') }}">
    
    
    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/css/app.css'])
    <!-- マニフェストファイルの読み込み
    <link rel="manifest" href="{{ asset('manifest.json') }}"> -->
    <!-- マニフェストファイルを動的に生成する -->
    <link rel="manifest" href="{{ url('/manifest.json') }}">

    <!-- サービスワーカーの登録 -->
    <script src="{{ asset('registerSW.js') }}"></script>

    <!-- google広告 -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1093408896428535"
    crossorigin="anonymous"></script>
    
    <style>
        .dropdown-item {
            padding: 0.5rem 1.0rem; /* メニューアイテムのパディングを調整 */
            /*margin-bottom: 1.0rem; /* メニューアイテムの下にスペースを追加 */
            /*line-height: 1.5; /* 行間の調整 */
        }
    </style>

</head>
<div class="header"></div>

<body>
    
    <div id="notification">
        <img src="" alt="">
    </div>

    @if (session('message'))
        @php
            $message = session('message');
            $message = str_replace("\n", "\\n", $message);
            $type = session('type');
            $sec = session('sec');
            session()->forget(['message', 'type', 'sec']);
        @endphp
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            showNotification('{{ $message }}', '{{ $type }}', '{{ $sec }}');
        });
        </script>
    @endif

    <!-- 共通ポップアップモーダル -->
    @include('modals.common-modal')

    <div class="container">
            <div id="app">
                <div class="fixed-top">
                    <div class="container-fluid fixed-top-menu mx-auto w-100">
                        <nav class="navbar navbar-light d-flex flex-nowrap align-items-center">
                        
                            <!-- アプリ名 -->
                            <a class="navbar-brand" href="{{ url('/') }}">{{ config('app.name', 'Application') }}</a>
                            <!-- ユーザー名 -->
                            <div class="flex-grow-1 d-flex justify-content-end overflow-hidden me-2" style="min-width: 0;">
                                @auth
                                    <span class="text-ellipsis">{{ Auth::user()->name }}</span>
                                @else
                                    <span class="text-ellipsis">ゲストユーザー</span>
                                @endauth
                            </div>
                            <!-- ハンバーガーメニュー -->
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                        </nav>
                        <!-- ナビゲーションメニュー -->
                        <div class="collapse navbar-collapse" id="navbarSupportedContent">
                            <!-- Left Side Of Navbar -->
                            <ul class="navbar-nav me-auto">

                            </ul>

                            <!-- Right Side Of Navbar -->
                            <ul class="navbar-nav ms-auto">
                                <!-- Authentication Links -->
                                <li class="nav-item dropdown">
                                @auth
                                    @if (Auth::user()->admin_flag)
                                        <a class="dropdown-item" href="{{ route('admin.home') }}">{{ __('Admin') }}</a>
                                    @endif
                                        <a class="dropdown-item" href="{{ route('profile.show') }}">{{ __('Profile') }}</a>
                                        <a class="dropdown-item" href="{{ route('request.index') }}">{{ __('Request') }}</a>
                                        <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            {{ __('Logout') }}
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                @else
                                    @if (Route::has('login'))
                                        <a class="dropdown-item" href="{{ route('login') }}">{{ __('Login') }}</a>
                                    @endif
                                    @if (Route::has('register'))
                                        <a class="dropdown-item" href="{{ route('register') }}">{{ __('Register') }}</a>
                                    @endif
                                @endauth
                                    <a id="add-to-home-screen" class="dropdown-item" href="#">{{ __('Install to Application') }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <main class="py-3">
                    <div class="container-fluid fixed-main">
                    @yield('content')
                    </div>
                </main>
            </div>
            <div class="footer">
                <?//ナビ?>   
                @include('layouts.nav_menu')
            </div>
        </div>
    
    </div>
    
    <!-- Scripts -->
    @vite(['resources/js/app.js'])
</body>
</html>

