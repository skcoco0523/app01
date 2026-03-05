<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;

//管理者
use App\Http\Middleware\AdminMiddleware;

use App\Http\Controllers\Admin\AdminHomeController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminAdvController;
use App\Http\Controllers\Admin\AdminSmartRemoteController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminAnotherController;

//ユーザー
use App\Http\Controllers\Auth\VerificationController;   //メールアドレス認証対応
use App\Http\Controllers\Auth\UserController;
use App\Http\Controllers\Auth\UserDeviceController;
use App\Http\Controllers\Auth\LineLoginController;
use App\Http\Controllers\FriendlistController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\RouletteController;
use App\Http\Controllers\SmartRemoteController;
use App\Http\Controllers\IotDeviceController;


Auth::routes();

// 未認証ユーザー向け
Route::get('/', [HomeController::class, 'index'])->name('home');


Route::get('linelogin', [LineLoginController::class, 'lineLogin'])->name('linelogin');
Route::get('callback', [LineLoginController::class, 'callback'])->name('callback');

//PWAアプリインストール時の　デバイス情報登録
Route::post('devices/check', [UserDeviceController::class, 'device_update'])->name('devices-check');

//パスワードリセット
Route::post('password/reset/mailsend', [UserController::class, 'password_reset_mailsend'])->name('password-reset');

Route::get('roulette/show', [RouletteController::class, 'roulette_show'])->name('roulette-show');



//-------------------------------------------------------------------------------------------------------
// 認証済み　メール未認証ユーザー向けルート
//-------------------------------------------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    //プロフィール
    Route::get('profile/show', [UserController::class, 'profile_show'])->name('profile-show');
    Route::post('profile/change', [UserController::class, 'profile_change'])->name('profile-change');

    //要望・問い合わせ
    Route::get('request/show', [RequestController::class, 'request_show'])->name('request-show');
    Route::post('request/send', [RequestController::class, 'request_send'])->name('request-send');

    //-------------------------------------------------------------------------------------------------------
    //メールアドレス認証用ルート　XXXXX-XXXXXの定義にしたいが、Laravelの定義に合わせてXXXXX.XXXXXに変更
    //-------------------------------------------------------------------------------------------------------
    // メール認証通知ページ
    Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
    // 確認メール送信
    Route::post('/email/verification-notification', [VerificationController::class, 'send'])->name('verification.send')
        ->middleware('throttle:6,1');   //リクエストの頻度制限　1分間に6回まで
    // メールリンククリックで認証完了
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])->name('verification.verify')
        ->middleware(['signed']);   //署名付きURLを検証するミドルウェア
});

//-------------------------------------------------------------------------------------------------------
// 管理者権限不要
//-------------------------------------------------------------------------------------------------------
Route::middleware(['auth', 'verified'])->group(function () {

    //-------------------------------------------------------------------------------------------------------
    //フレンドリスト表示
    //-------------------------------------------------------------------------------------------------------
    Route::get('friendlist/show', [FriendlistController::class, 'show'])->name('friendlist-show');
    //フレンド情報表示
    Route::get('friend/show', [FriendlistController::class, 'detail'])->name('friend-show');
    //フレンド申請
    Route::post('friend/request', [FriendlistController::class, 'request'])->name('friend-request');
    //フレンド承認
    Route::post('friend/accept', [FriendlistController::class, 'accept'])->name('friend-accept');
    //フレンド申請拒否
    Route::post('friend/decline', [FriendlistController::class, 'decline'])->name('friend-decline');
    //フレンド申請キャンセル
    Route::post('friend/cancel', [FriendlistController::class, 'cancel'])->name('friend-cancel');

    //-------------------------------------------------------------------------------------------------------
    //スマートリモコンリスト表示
    //-------------------------------------------------------------------------------------------------------
    //スマートリモコン一覧　デバイス一覧
    Route::get('smart-remote/show', [SmartRemoteController::class, 'remote_show'])->name('remote-show');
    //スマートリモコン詳細
    Route::get('smart-remote/show/detail', [SmartRemoteController::class, 'remote_show_detail'])->name('remote-show-detail');
    //スマートリモコン登録
    Route::post('smart-remote/reg', [SmartRemoteController::class, 'remote_reg'])->name('remote-reg');
    //スマートリモコン詳細変更
    Route::post('smart-remote/chg', [SmartRemoteController::class, 'remote_chg'])->name('remote-chg');
    //スマートリモコン削除
    Route::post('smart-remote/del', [SmartRemoteController::class, 'remote_del'])->name('remote-del');
    //スマートリモコン共有解除
    Route::post('smart-remote/unshare', [SmartRemoteController::class, 'remote_unshare'])->name('remote-unshare');

    //-------------------------------------------------------------------------------------------------------
    //デバイス詳細
    //-------------------------------------------------------------------------------------------------------
    //デバイス詳細
    Route::get('iotdevice/show/detail', [IotDeviceController::class, 'iotdevice_show_detail'])->name('iotdevice-show-detail');
    //デバイス登録
    Route::post('iotdevice/reg', [IotDeviceController::class, 'iotdevice_reg'])->name('iotdevice-reg');
    //デバイス詳細変更
    Route::post('iotdevice/chg', [IotDeviceController::class, 'iotdevice_chg'])->name('iotdevice-chg');
    //スマートリモコン削除
    Route::post('iotdevice/del', [IotDeviceController::class, 'iotdevice_del'])->name('iotdevice-del');

    //-------------------------------------------------------------------------------------------------------
    //メモ
    //-------------------------------------------------------------------------------------------------------
    //メモ一覧
    Route::get('note/show', [NoteController::class, 'note_show'])->name('note-show');
    //メモ詳細
    Route::get('note/show/detail', [NoteController::class, 'note_show_detail'])->name('note-show-detail');
    //メモ登録
    Route::post('note/reg', [NoteController::class, 'note_reg'])->name('note-reg');
    //メモ詳細変更
    Route::post('note/chg', [NoteController::class, 'note_chg'])->name('note-chg');
    //メモ削除
    Route::post('note/del', [NoteController::class, 'note_del'])->name('note-del');
    //メモ共有　API経由で実施
    //メモ共有解除
    Route::post('note/unshare', [NoteController::class, 'note_unshare'])->name('note-unshare');

});


//-------------------------------------------------------------------------------------------------------
// 管理者権限必須
//-------------------------------------------------------------------------------------------------------
Route::middleware(['auth', 'verified', AdminMiddleware::class])->group(function () {
    Route::group(['prefix' => 'admin'], function(){

        Route::get('home', [AdminHomeController::class, 'home'])->name('admin-home');

        //----------------------------------------------------------------------------------
        //ユーザー
        //----------------------------------------------------------------------------------
        //一覧
        Route::get('user/search', [AdminUserController::class, 'user_search'])->name('admin-user-search');
        Route::post('user/search/chg', [AdminUserController::class, 'user_chg'])->name('admin-user-chg');
        //依頼・要望
        Route::get('user/repuest', [AdminUserController::class, 'user_request_search'])->name('admin-request-search');
        Route::post('user/repuest/chg', [AdminUserController::class, 'user_request_chg'])->name('admin-request-chg');
        //----------------------------------------------------------------------------------


        //----------------------------------------------------------------------------------
        //IoTデバイス
        //----------------------------------------------------------------------------------
        //デバイス一覧
        Route::get('iotdevice/search', [AdminSmartRemoteController::class, 'iotdevice_search'])->name('admin-iotdevice-search');
        //デバイス登録
        Route::get('iotdevice/reg', [AdminSmartRemoteController::class, 'iotdevice_regist'])->name('admin-iotdevice-reg');
        Route::post('iotdevice/reg', [AdminSmartRemoteController::class, 'iotdevice_reg'])->name('admin-iotdevice-reg');
        //デバイス検索>変更
        Route::post('iotdevice/chg', [AdminSmartRemoteController::class, 'iotdevice_chg'])->name('admin-iotdevice-chg');
        //デバイス検索>削除
        Route::post('iotdevice/del', [AdminSmartRemoteController::class, 'iotdevice_del'])->name('admin-iotdevice-del');
        //----------------------------------------------------------------------------------


        //----------------------------------------------------------------------------------
        //リモコン
        //----------------------------------------------------------------------------------
        //リモコンデザイン一覧
        Route::get('virtualremote-blade/search', [AdminSmartRemoteController::class, 'virtualremote_blade_search'])->name('admin-virtualremote-blade-search');
        //リモコンデザイン登録
        Route::get('virtualremote-blade/reg', [AdminSmartRemoteController::class, 'virtualremote_blade_regist'])->name('admin-virtualremote-blade-reg');
        Route::post('virtualremote-blade/reg', [AdminSmartRemoteController::class, 'virtualremote_blade_reg'])->name('admin-virtualremote-blade-reg');
        //リモコンデザイン検索>変更
        Route::post('virtualremote-blade/chg', [AdminSmartRemoteController::class, 'virtualremote_blade_chg'])->name('admin-virtualremote-blade-chg');
        //リモコンデザイン検索>削除
        Route::post('virtualremote-blade/del', [AdminSmartRemoteController::class, 'virtualremote_blade_del'])->name('admin-virtualremote-blade-del');
        //リモコンデザインチェック
        Route::get('virtualremote-blade/preview', [AdminSmartRemoteController::class, 'virtualremote_blade_preview'])->name('admin-virtualremote-blade-preview');
        //----------------------------------------------------------------------------------

        
        //----------------------------------------------------------------------------------
        //広告
        //----------------------------------------------------------------------------------
        //登録
        Route::get('adv/reg', [AdminAdvController::class, 'adv_regist'])->name('admin-adv-reg');
        Route::post('adv/reg', [AdminAdvController::class, 'adv_reg'])->name('admin-adv-reg');

        //検索
        Route::get('adv/search', [AdminAdvController::class, 'adv_search'])->name('admin-adv-search');
        //検索>変更
        Route::post('adv/search/chg', [AdminAdvController::class, 'adv_chg'])->name('admin-adv-chg');
        //検索>削除
        Route::post('adv/search/del', [AdminAdvController::class, 'adv_del'])->name('admin-adv-del');
        //----------------------------------------------------------------------------------

        
        //----------------------------------------------------------------------------------
        //通知
        //----------------------------------------------------------------------------------
        Route::get('notification/search', [AdminNotificationController::class, 'notification'])->name('admin-notification');
        //メール通知
        Route::post('notification/mail', [AdminNotificationController::class, 'admin_mail_send'])->name('admin-mail-send');
        //プッシュ通知
        Route::post('notification/push', [AdminNotificationController::class, 'admin_push_send'])->name('admin-push-send');
        //----------------------------------------------------------------------------------



        //----------------------------------------------------------------------------------
        //その他
        //----------------------------------------------------------------------------------
        //メモ検索
        Route::get('another/memo-search', [AdminAnotherController::class, 'memo_search'])->name('admin-memo-search');
        //検索>登録
        Route::post('another/memo-search/reg', [AdminAnotherController::class, 'memo_reg'])->name('admin-memo-reg');
        //検索>変更
        Route::post('another/memo-search/chg', [AdminAnotherController::class, 'memo_chg'])->name('admin-memo-chg');
        //検索>削除
        Route::post('another/memo-search/del', [AdminAnotherController::class, 'memo_del'])->name('admin-memo-del');
        //----------------------------------------------------------------------------------
    });
});


//PWA用マニフェストファイルを動的に生成
Route::get('/manifest.json', function () {
    // .env から環境変数を取得
    $domain = env('SUB_DOMAIN');
    $appName = env('APP_NAME');
    $projectName = env('PROJECT_NAME'); // 例: 'app01'
    // ローカル環境であるか判定
    $isLocal = $domain === 'localhost';
    // ベースパスの決定: ローカルなら /app01/ 、本番なら /
    $basePath = $isLocal ? "/{$projectName}/" : "/";

    $manifest = [
        "name" => $appName,
        "short_name" => $appName,
        "description" => 'アプリリスト',
        // start_url を動的に変更　末尾スラッシュなしで定義するのが一般的
        "start_url" => $isLocal ? "/{$projectName}" : "/", 
        
        "display" => "standalone",
        "background_color" => "#ffffff",
        "theme_color" => "#000000",
        "icons" => [
            [
                // ★修正点 2: アイコンのsrcを動的に変更
                "src" => "{$basePath}img/icon/home_icon_192_192.png",
                "sizes" => "192x192",
                "type" => "image/png"
            ],
            [
                "src" => "{$basePath}img/icon/home_icon_512_512.png",
                "sizes" => "512x512",
                "type" => "image/png"
            ]
        ]
    ];

    return response()->json($manifest)
        ->header('Content-Type', 'application/json');
});