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
                ['url' => route('admin.adv.config'), 'label' => '広告設定'],
            ]
        ],
        'notification' => [
            'title' => '通知',
            'items' => [
                ['url' => route('admin.notification.index', ['send_type' => 'mail']), 'label' => 'メール通知'],
                ['url' => route('admin.notification.index', ['send_type' => 'push']), 'label' => 'プッシュ通知'],
            ]
        ],
        'game' => [
            'title' => 'ゲーム',
            'items' => [
                ['url' => route('admin.game.index'), 'label' => 'ゲーム一覧'],
                ['label' => '<br><span class="text-muted small fw-bold">【マスターデータ】</span>'],
                ['url' => route('admin.game.character.index'), 'label' => 'キャラクター管理'],
                ['url' => route('admin.game.map.index'), 'label' => 'マップ管理'],
                ['url' => route('admin.game.stage.index'), 'label' => 'ステージ管理'],
                ['url' => route('admin.game.item.index'), 'label' => '武器・アイテム管理'],
                ['label' => '<br><span class="text-muted small fw-bold">【デザイナーツール】</span>'],
                ['url' => route('admin.game.sprite_sheet.index'), 'label' => 'スプライトシート管理'],
                ['url' => route('admin.game.pixel_parts.index'), 'label' => 'ピクセルパーツ管理'],
                ['url' => route('admin.game.grid_parts.index'), 'label' => 'グリッドパーツ管理'],
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
    // 各画面へのマッピング分岐
    if ($tab1 == 'iotdevice' && $tab2 == 'create' && $tab3 == '') {
        $view_right_file    = 'admin.admin_iotdevice_create';
    }elseif ($tab1 == 'iotdevice' && $tab2 == 'search' && $tab3 == '') {
        $view_left_file     = 'admin.admin_iotdevice_search_left';
        $view_right_file    = 'admin.admin_iotdevice_search';
    }elseif ($tab1 == 'virtualremote-blade' && $tab2 == 'create' && $tab3 == '') {
        $view_right_file    = 'admin.admin_virtualremoteblade_create';
    }elseif ($tab1 == 'virtualremote-blade' && $tab2 == 'search' && $tab3 == '') {
        $view_left_file     = 'admin.admin_virtualremoteblade_search_left';
        $view_right_file    = 'admin.admin_virtualremoteblade_search';
    }elseif ($tab1 == 'user' && $tab2 == 'create' && $tab3 == '') {
    }elseif ($tab1 == 'user' && $tab2 == 'search' && $tab3 == '') {
        $view_left_file     = 'admin.admin_user_search_left';
        $view_right_file    = 'admin.admin_user_search';
    }elseif ($tab1 == 'user' && $tab2 == 'request' && $tab3 == 'create') {
    }elseif ($tab1 == 'user' && $tab2 == 'request' && $tab3 == 'search') {
        $view_left_file     = 'admin.admin_request_search_left';
        $view_right_file    = 'admin.admin_request_search';
    }elseif ($tab1 == 'adv' && $tab2 == 'create' && $tab3 == '') {
        $view_right_file    = 'admin.admin_adv_create';
    }elseif ($tab1 == 'adv' && $tab2 == 'search' && $tab3 == '') {
        $view_left_file     = 'admin.admin_adv_search_left';
        $view_right_file    = 'admin.admin_adv_search';
    }elseif ($tab1 == 'adv' && $tab2 == 'config' && $tab3 == '') {
        $view_right_file    = 'admin.admin_adv_config';
    }elseif ($tab1 == 'notification' && $tab2 == 'search' && $tab3 == '') {
        $view_left_file     = 'admin.admin_notification_left';
        $view_right_file    = 'admin.admin_notification';
    
    //=============================================================
    // ゲーム関連画面の分岐
    }elseif ($tab1 == 'game' && $tab2 == 'common' && $tab3 == 'search') {
        $view_left_file     = 'admin.game.admin_game_list_left';
        $view_right_file    = 'admin.game.admin_game_list';
    }elseif ($tab1 == 'game' && $tab2 == 'character' && $tab3 == 'search') {
        $view_left_file     = 'admin.game.admin_character_left';
        $view_right_file    = 'admin.game.admin_character';
    }elseif ($tab1 == 'game' && $tab2 == 'map' && $tab3 == 'search') {
        $view_left_file     = 'admin.game.admin_map_left';
        $view_right_file    = 'admin.game.admin_map';
    }elseif ($tab1 == 'game' && $tab2 == 'stage' && $tab3 == 'search') {
        $view_left_file     = 'admin.game.admin_stage_left';
        $view_right_file    = 'admin.game.admin_stage';
    }elseif ($tab1 == 'game' && $tab2 == 'item' && $tab3 == 'search') {
        $view_left_file     = 'admin.game.admin_item_left';
        $view_right_file    = 'admin.game.admin_item';
        
    }elseif ($tab1 == 'game' && $tab2 == 'sprite-sheet' && $tab3 == '') {
        // 🌟【修正】スプライトシート管理 ＝ 純粋な画像倉庫
        $view_left_file     = 'admin.game.admin_game_sprite_sheet_left';
        $view_right_file    = 'admin.game.admin_game_sprite_sheet';

    }elseif ($tab1 == 'game' && $tab2 == 'pixel-parts' && $tab3 == '') {
        // 🌟【新設】ピクセルパーツ管理
        $view_left_file     = 'admin.game.admin_game_sprite_sheet_left';
        $view_right_file    = 'admin.game.admin_game_pixel_parts';

    }elseif ($tab1 == 'game' && $tab2 == 'grid-parts' && $tab3 == '') {
        // 🌟【新設】グリッドパーツ管理
        $view_left_file     = 'admin.game.admin_game_sprite_sheet_left';
        $view_right_file    = 'admin.game.admin_game_grid_parts';

    }elseif ($tab1 == 'game' && $tab2 == 'asset' && $tab3 == '') {
        // 🌟【修正】画像アセット管理 ＝ 職人部屋エディタ本体
        $view_left_file     = 'admin.game.admin_game_asset_left';
        $view_right_file    = 'admin.game.admin_game_asset';

    }elseif ($tab1 == 'another' && $tab2 == 'memo' && $tab3 == 'search') {
        $view_left_file     = 'admin.admin_memo_search_left';
        $view_right_file    = 'admin.admin_memo_search';
    }

@endphp

<div class="container-fluid" style="width: 100%;">
    <div class="row">
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
        <div class="col-12 col-md-10">
            <div class="rounded border p-3 mb-2">
                @includeIf($view_right_file)
            </div>
        </div>
    </div>
</div>
@endsection