
<!--<link rel="stylesheet" href="{{ asset('/css/style.css') }}">-->
@extends('admin.app')

@section('content')

@php
    //メニュー切り替え
    $segments = request()->segments();

    // `tab` パラメータを取得（クエリパラメータとして）
    $tab1 = request()->query('tab1');   

    // `tab` パラメータが存在しない場合に URL のセグメントを取得
    if (!$tab1) $tab1 = $segments[1] ?? null;

    //admin_home_right.blade.php　で表示する情報
    $tab2 = $segments[2] ?? null;
    $tab3 = $segments[3] ?? null;

    $view_left_file = null;
    $view_right_file = null;

    // メニュー項目の定義
    $menu_configs = [
        'iotdevice' => [
            'title' => 'デバイス',
            'items' => [
                ['url' => route('admin.iotdevice.index'), 'label' => '検索/変更/削除'],
            ]
        ],
        'virtualremote-blade' => [
            'title' => 'リモコン',
            'items' => [
                ['label' => '<br>デザイン'],
                ['url' => route('admin.virtualremote.blade.create'), 'label' => '新規登録'],
                ['url' => route('admin.virtualremote.blade.index'), 'label' => '検索/変更/削除'],
                ['label' => '<br>ユーザー別'],
                ['url' => route('admin.virtualremote.blade.create'), 'label' => '新規登録'],
                ['url' => route('admin.virtualremote.blade.index'), 'label' => '検索/変更/削除'],
            ]
        ],
        'user' => [
            'title' => 'ユーザー',
            'items' => [
                ['url' => route('admin.user.index'), 'label' => 'ユーザー'],
                ['url' => route('admin.user.request.index'), 'label' => '要望・問い合わせ'],
            ]
        ],
        'adv' => [
            'title' => '広告',
            'items' => [
                ['url' => route('admin.adv.create'), 'label' => '新規登録'],
                ['url' => route('admin.adv.index'), 'label' => '検索/変更/削除'],
            ]
        ],
        'notification' => [
            'title' => '通知',
            'items' => [
                ['url' => route('admin.notification.index', ['send_type' => 'mail']), 'label' => 'メール通知'],
                ['url' => route('admin.notification.index', ['send_type' => 'push']), 'label' => 'プッシュ通知'],
            ]
        ],
        'another' => [
            'title' => 'その他',
            'items' => [
                ['url' => route('admin.memo.index'), 'label' => 'メモ'],
            ]
        ],
    ];

    $current_menu = $menu_configs[$tab1] ?? null;

    //=============================================================
    // iotデバイスの検索画面
    if ($tab1 == 'iotdevice' && $tab2 == 'create' && $tab3 == '') {
        $view_right_file    = 'admin.admin_iotdevice_create';
    }elseif ($tab1 == 'iotdevice' && $tab2 == 'search' && $tab3 == '') {
        $view_left_file     = 'admin.admin_iotdevice_search_left';
        $view_right_file    = 'admin.admin_iotdevice_search';
    //=============================================================
    // スマートリモコンの検索画面
    }elseif ($tab1 == 'virtualremote-blade' && $tab2 == 'create' && $tab3 == '') {
        $view_right_file    = 'admin.admin_virtualremoteblade_create';
    }elseif ($tab1 == 'virtualremote-blade' && $tab2 == 'search' && $tab3 == '') {
        $view_left_file     = 'admin.admin_virtualremoteblade_search_left';
        $view_right_file    = 'admin.admin_virtualremoteblade_search';
    //=============================================================
    // ユーザー一覧
    }elseif ($tab1 == 'user' && $tab2 == 'create' && $tab3 == '') {
        //ユーザーの新規登録はないため、右側は空白のまま
    }elseif ($tab1 == 'user' && $tab2 == 'search' && $tab3 == '') {
        $view_left_file     = 'admin.admin_user_search_left';
        $view_right_file    = 'admin.admin_user_search';
    //=============================================================
    // ユーザーリクエスト
    }elseif ($tab1 == 'user' && $tab2 == 'request' && $tab3 == 'create') {
        //ユーザーリクエストの新規登録はないため、右側は空白のまま
    }elseif ($tab1 == 'user' && $tab2 == 'request' && $tab3 == 'search') {
        $view_left_file     = 'admin.admin_request_search_left';
        $view_right_file    = 'admin.admin_request_search';
    //=============================================================
    // 広告
    }elseif ($tab1 == 'adv' && $tab2 == 'create' && $tab3 == '') {
        $view_right_file    = 'admin.admin_adv_create';
    }elseif ($tab1 == 'adv' && $tab2 == 'search' && $tab3 == '') {
        $view_left_file     = 'admin.admin_adv_search_left';
        $view_right_file    = 'admin.admin_adv_search';
    //=============================================================
    // 通知
    }elseif ($tab1 == 'notification' && $tab2 == 'search' && $tab3 == '') {
        $view_left_file     = 'admin.admin_notification_left';
        $view_right_file    = 'admin.admin_notification';
    //=============================================================
    // その他（メモ）
    }elseif ($tab1 == 'another' && $tab2 == 'memo' && $tab3 == 'search') {
        $view_left_file     = 'admin.admin_memo_search_left';
        $view_right_file    = 'admin.admin_memo_search';
    }

@endphp

<div class="container-fluid" style="width: 100%;">
    <div class="row">
        {{-- メニュー選択したタブによって切り替え --}}
        <div class="col-12 col-md-2">
            <div class="rounded border p-3 mb-2">
                <div class="menu_section">
                    @if($current_menu)
                        {!! $current_menu['title'] !!}
                        @foreach($current_menu['items'] as $item)
                            @if(isset($item['url']))
                                <li><a href="{{ $item['url'] }}">{!! $item['label'] !!}</a></li>
                            @else
                                {!! $item['label'] !!}
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="rounded border p-3">
                @includeIf($view_left_file)
            </div>
        </div>
        <!--メイン-->
        <div class="col-12 col-md-10">
            <div class="rounded border p-3 mb-2">
                @includeIf($view_right_file)
            </div>
        </div>
    </div>
</div>
@endsection
