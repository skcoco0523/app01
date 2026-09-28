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
                ['label' => '<br>ESP'],
                ['url' => route('admin.iotdevice.index'), 'label' => '検索/変更/削除'],
                ['label' => '<br>OLED'],
                ['url' => route('admin.oled.index'), 'label' => '検索/変更/削除'],
            ]
        ],
        'oled-face' => [
            'title' => 'OLED',
            'items' => [
                ['url' => route('admin.oled.parts.index'), 'label' => 'パーツ管理 (切出)'],
                ['url' => route('admin.oled.create'), 'label' => '顔アニメ新規登録'],
                ['url' => route('admin.oled.index'), 'label' => '顔アニメ一覧・検索'],
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
        'point' => [
            'title' => 'ポイント',
            'items' => [
                ['url' => route('admin.point.config_pack'), 'label' => 'パック設定'],
                ['url' => route('admin.point.config_free'), 'label' => '無償ポイント設定'],
                ['url' => route('admin.point.config_amount'), 'label' => 'ポイント額設定'],
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
        'system' => [
            'title' => 'システム設定',
            'items' => [
                ['url' => route('admin.system.config_maint'), 'label' => 'メンテナンス設定'],
                ['url' => route('admin.system.config_mqtt'), 'label' => 'MQTT設定'],
                ['url' => route('admin.system.ai_test'), 'label' => 'AIテスト'],
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
    // 各画面へのマッピング分岐（$tab1 優先の階層型構造）
    //=============================================================
    if ($tab1 == 'iotdevice') {
        if ($tab2 == 'search' && $tab3 == '') {
            $view_left_file     = 'admin.admin_iotdevice_search_left';
            $view_right_file    = 'admin.admin_iotdevice_search';
        }
    } elseif ($tab1 == 'oled-face') {
        if ($tab2 == 'parts' && $tab3 == '') {
            $view_left_file     = 'admin.admin_oled_parts_left';
            $view_right_file    = 'admin.admin_oled_parts_manager';
        } elseif ($tab2 == 'search' && $tab3 == '') {
            $view_left_file     = 'admin.admin_oled_search_left';
            $view_right_file    = 'admin.admin_oled_search';
        } elseif ($tab2 == 'create' || $tab2 == 'edit' || $tab2 == 'parts') {
            $view_left_file     = 'admin.admin_oled_search_left';
            $view_right_file    = 'admin.admin_oled_editor';
        }
    } elseif ($tab1 == 'virtualremote-blade') {
        if ($tab2 == 'create' && $tab3 == '') {
            $view_right_file    = 'admin.admin_virtualremoteblade_create';
        } elseif ($tab2 == 'search' && $tab3 == '') {
            $view_left_file     = 'admin.admin_virtualremoteblade_search_left';
            $view_right_file    = 'admin.admin_virtualremoteblade_search';
        }
    } elseif ($tab1 == 'user') {
        if ($tab2 == 'search' && $tab3 == '') {
            $view_left_file     = 'admin.admin_user_search_left';
            $view_right_file    = 'admin.admin_user_search';
        } elseif ($tab2 == 'request' && $tab3 == 'search') {
            $view_left_file     = 'admin.admin_request_search_left';
            $view_right_file    = 'admin.admin_request_search';
        }
    } elseif ($tab1 == 'adv') {
        if ($tab2 == 'create' && $tab3 == '') {
            $view_right_file    = 'admin.admin_adv_create';
        } elseif ($tab2 == 'search' && $tab3 == '') {
            $view_left_file     = 'admin.admin_adv_search_left';
            $view_right_file    = 'admin.admin_adv_search';
        } elseif ($tab2 == 'config' && $tab3 == '') {
            $view_right_file    = 'admin.admin_adv_config';
        }
    } elseif ($tab1 == 'point') {
        if (in_array($tab2, ['config_pack', 'config_free', 'config_amount']) && $tab3 == '') {
            $view_right_file    = 'admin.admin_point_config';
        }
    } elseif ($tab1 == 'notification') {
        if ($tab2 == 'search' && $tab3 == '') {
            $view_left_file     = 'admin.admin_notification_left';
            $view_right_file    = 'admin.admin_notification';
        }
    } elseif ($tab1 == 'game') {
        if ($tab2 == 'common' && $tab3 == 'search') {
            $view_left_file     = 'admin.game.admin_game_list_left';
            $view_right_file    = 'admin.game.admin_game_list';
        } elseif ($tab2 == 'character' && $tab3 == 'search') {
            $view_left_file     = 'admin.game.admin_character_left';
            $view_right_file    = 'admin.game.admin_character';
        } elseif ($tab2 == 'map' && $tab3 == 'search') {
            $view_left_file     = 'admin.game.admin_map_left';
            $view_right_file    = 'admin.game.admin_map';
        } elseif ($tab2 == 'stage' && $tab3 == 'search') {
            $view_left_file     = 'admin.game.admin_stage_left';
            $view_right_file    = 'admin.game.admin_stage';
        } elseif ($tab2 == 'item' && $tab3 == 'search') {
            $view_left_file     = 'admin.game.admin_item_left';
            $view_right_file    = 'admin.game.admin_item';      
        } elseif ($tab2 == 'sprite-sheet' && $tab3 == '') {
            $view_left_file     = 'admin.game.admin_game_sprite_sheet_left';
            $view_right_file    = 'admin.game.admin_game_sprite_sheet';
        } elseif ($tab2 == 'pixel-parts' && $tab3 == '') {
            $view_left_file     = 'admin.game.admin_game_sprite_sheet_left';
            $view_right_file    = 'admin.game.admin_game_pixel_parts';
        } elseif ($tab2 == 'grid-parts' && $tab3 == '') {
            $view_left_file     = 'admin.game.admin_game_sprite_sheet_left';
            $view_right_file    = 'admin.game.admin_game_grid_parts';
        } elseif ($tab2 == 'asset' && $tab3 == '') {
            $view_left_file     = 'admin.game.admin_game_asset_left';
            $view_right_file    = 'admin.game.admin_game_asset';
        }
    } elseif ($tab1 == 'system') {
        if (in_array($tab2, ['config_maint', 'config_mqtt']) && $tab3 == '') {
            $view_right_file    = 'admin.admin_system_config';
        } elseif ($tab2 == 'ai_test') {
            $view_right_file    = 'admin.system.ai_test';
        }
    } elseif ($tab1 == 'another') {
        if ($tab2 == 'memo' && $tab3 == 'search') {
            $view_left_file     = 'admin.admin_memo_search_left';
            $view_right_file    = 'admin.admin_memo_search';
        }
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