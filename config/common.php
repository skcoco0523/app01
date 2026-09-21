<?php

//設定ファイルを追加・変更したら以下のコマンドで設定キャッシュを再生成
//php artisan config:cache

return [

    'admin_memo_path' => storage_path('app/admin_memo'), // 管理者メモの保存ディレクトリ
    'smart_remote_blade_paht' => 'smart_remote', // スマートリモコンデザインの保存ディレクトリ

    //=========================================================================
    //フレンドリスト
    //=========================================================================
    'friend_status' => [
        'pending'  => 0, // 未承認
        'accepted' => 1, // 承認済み
        'declined' => 2, // 拒否済み
    ],

    //=========================================================================
    // ユーザーリクエスト
    //=========================================================================
    'request_type' => [
        'request'  => 0, // 要望
        'inquiry' => 1, // 問い合わせ
    ],
    
    'request_status' => [
        'unresolved' => 0, // 未対応
        'resolved'   => 1, // 対応済
    ],

    //=========================================================================
    // IoTデバイスステータス
    //=========================================================================
    'iot_device_status' => [
        'offline'           => 0,
        'online'            => 1,
        'requesting'        => 2,
        'ir_standby'        => 3,
        'ir_received'       => 4,
        'ir_timeout'        => 5,
        'ping_requesting'   => 10,
    ],

    //=============================================================================================
    // IoTデバイス管理（ID範囲に基づく）
    // 表示用アイコン (アイコン情報：https://fontawesome.com/)
    //=============================================================================================
    // カテゴリーのID範囲定義（ロジック用）
    'device_range' => [
        'HUB'      => [1, 99],      // 1-99: 制御デバイス (HUB)             全体を制御するハブデバイス 常時MQTT接続。
        'ACTUATOR' => [100, 299],   // 100-299: 動作デバイス (ACTUATOR)     ハブからの信号で動作するデバイス
        'TRIGGER'  => [300, 499],   // 300-499: トリガーデバイス (TRIGGER)  ハブへ通知して制御を促すデバイス
    ],
    'device_info' => [
        // 1-99
        1   => ['type_name' => 'ｽﾏｰﾄﾎｰﾑ',       'ww_flag' => true,   'ai_flag' => true,       'icon_class' => 'fa-tower-broadcast',         'description' => 'スマートデバイス全体の司令塔',],
        // 100-299
        101 => ['type_name' => 'ｽﾏｰﾄﾛｯｸ',       'ww_flag' => false,   'ai_flag' => false,      'icon_class' => 'fa-lock',                     'description' => '自宅等の施錠・解錠',],
        102 => ['type_name' => 'ｽﾏｰﾄﾌﾞﾗｲﾝﾄﾞ',     'ww_flag' => false,   'ai_flag' => false,      'icon_class' => 'fa-scroll',                   'description' => '昇降・開閉',],
        // 300-499
        301 => ['type_name' => '認証ﾘｰﾀﾞｰ',      'ww_flag' => false,   'ai_flag' => false,        'icon_class' => 'fa-address-card',             'description' => 'ICカードやスマホによる解錠トリガー',],
        302 => ['type_name' => '人感ｾﾝｻｰ',      'ww_flag' => false,   'ai_flag' => false,        'icon_class' => 'fa-person-walking',           'description' => '人の動きを検知して通知',],
    ],

    //=========================================================================
    // TTS音声設定
    //=========================================================================
    'tts_voices' => [
        // 女性ボイス
        'female_1'   => ['name' => '女性 1 (標準)',       'description' => '明るくクリアな標準ボイス',           'gender' => 'female'],
        'female_2'   => ['name' => '女性 2 (落ち着き)',   'description' => 'しっとりとした静かなトーン',         'gender' => 'female'],
        'female_3'   => ['name' => '女性 3 (元気・アニメ)', 'description' => 'ハキハキとした可愛らしいアニメ風',   'gender' => 'female'],
        'female_4'   => ['name' => '女性 4 (アナウンサー)', 'description' => '知性的でフォーマルなナレーション',   'gender' => 'female'],

        // 男性ボイス
        'male_1'     => ['name' => '男性 1 (標準)',       'description' => '聞き取りやすい標準ボイス',           'gender' => 'male'],
        'male_2'     => ['name' => '男性 2 (低音)',       'description' => '深みのある落ち着いた低音',           'gender' => 'male'],
        'male_3'     => ['name' => '男性 3 (爽やか)',     'description' => '若々しく親しみやすい青年ボイス',     'gender' => 'male'],
        'male_4'     => ['name' => '男性 4 (渋い・大人)', 'description' => '重厚感のあるダンディな大人ボイス',   'gender' => 'male'],

        // キャラクター・キッズ系
        'child_1'    => ['name' => 'キッズ (元気)',       'description' => 'かわいらしく元気な子供の声',         'gender' => 'neutral'],
        'robot_1'    => ['name' => 'ロボット (メカ)',     'description' => '少し機械音の混ざった近未来ボイス',     'gender' => 'neutral'],
        'butler_1'   => ['name' => '執事 (コンシェルジュ)','description' => '丁寧で礼儀正しい紳士の音声',         'gender' => 'male'],

        // 英語・多言語
        'en_female_1' => ['name' => 'English (Female)',   'description' => 'Standard US English Female Voice',   'gender' => 'female'],
        'en_male_1'   => ['name' => 'English (Male)',     'description' => 'Standard US English Male Voice',     'gender' => 'male'],
    ],
    //=========================================================================
    // 仮想リモコン
    //=========================================================================
    'virtual_remote' => [
        0  => ['name' => 'テレビ',           'icon' => 'fa-tv'],
        1  => ['name' => '照明',             'icon' => 'fa-lightbulb'],
        2  => ['name' => 'エアコン',         'icon' => 'fa-wind'],
        3  => ['name' => 'ロボット掃除機',   'icon' => 'fa-robot'],
        4  => ['name' => 'オーディオ',       'icon' => 'fa-volume-high'],
        5  => ['name' => 'プロジェクター',   'icon' => 'fa-video'],
        6  => ['name' => '扇風機',           'icon' => 'fa-fan'],
        7  => ['name' => 'ブルーレイ・DVD',  'icon' => 'fa-compact-disc'],
        99 => ['name' => 'その他',           'icon' => 'fa-question'],
    ],
    //=========================================================================
    // ユーザーメモ
    //=========================================================================
    'note_colors' => [
        0  => ['name' => 'ホワイト',    'code' => '#ffffff'],
        1  => ['name' => 'イエロー',    'code' => '#fff3cd'], // 薄い黄色
        2  => ['name' => 'スカイ',     'code' => '#cff4fc'], // 薄い水色
        3  => ['name' => 'グリーン',   'code' => '#d1e7dd'], // 薄い緑
        4  => ['name' => 'レッド',     'code' => '#f8d7da'], // 薄い赤（ピンク寄り）
        5  => ['name' => 'グレー',     'code' => '#e2e3e5'], // 薄い灰色
        6  => ['name' => 'ブルー',     'code' => '#cfe2ff'], // 薄い青
        7  => ['name' => 'パープル',   'code' => '#e1d5f2'], // 薄い紫（カスタム）
        8  => ['name' => 'オレンジ',   'code' => '#fde5d2'], // 薄い橙（カスタム）
        9  => ['name' => 'ピンク',     'code' => '#f9d6ea'], // 薄い桃（カスタム）
    ],
    //=========================================================================
    // ゲームアイテム(カテゴリ)
    //=========================================================================
    'game_items' => [
        0  => ['type' => 'item',        'name' => '🎒 アイテム'],
        1  => ['type' => 'stage_part',  'name' => '🧱 ステージ部品'],
        2  => ['type' => 'block',       'name' => '📦 仕掛けブロック（ハテナ・レンガ）'],
        3  => ['type' => 'hazard',      'name' => '⚠️ トラップ（トゲ・溶岩）'],
        4  => ['type' => 'gimmick',     'name' => '⚙️ ギミック（動く床・土管）'],
    ],
];