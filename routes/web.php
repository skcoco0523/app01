<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;

//管理者
use App\Http\Middleware\AdminMiddleware;

use App\Http\Controllers\Admin\AdminHomeController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\Admin\AdminAdvController;
use App\Http\Controllers\Admin\AdminIotDeviceController;
use App\Http\Controllers\Admin\AdminSmartRemoteController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminGameController;
use App\Http\Controllers\Admin\AdminGameCharacterController;
use App\Http\Controllers\Admin\AdminGameMapController;
use App\Http\Controllers\Admin\AdminGameStageController;
use App\Http\Controllers\Admin\AdminGameItemController;
use App\Http\Controllers\Admin\AdminGameAssetController;
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
use App\Http\Controllers\NoteController;
use App\Http\Controllers\GameController;


Auth::routes();

// 未認証ユーザー向け
Route::get('/', [HomeController::class, 'index'])->name('home');


Route::get('linelogin', [LineLoginController::class, 'lineLogin'])->name('linelogin');
Route::get('callback', [LineLoginController::class, 'callback'])->name('callback');

//PWAアプリインストール時の　デバイス情報登録
Route::post('devices/check', [UserDeviceController::class, 'device_update'])->name('devices.check');

//パスワードリセット
Route::post('password/reset/mailsend', [UserController::class, 'password_reset_mailsend'])->name('password.reset');

Route::get('roulette/show', [RouletteController::class, 'show'])->name('roulette.show');

// 共通ゲーム基盤（データドリブン用）
Route::get('play/{gameKey}', [GameController::class, 'play'])->name('games.play');


//-------------------------------------------------------------------------------------------------------
// 認証済み　メール未認証ユーザー向けルート
//-------------------------------------------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {
    //プロフィール
    Route::get('profile/show', [UserController::class, 'profile_show'])->name('profile.show');
    Route::post('profile/update', [UserController::class, 'profile_update'])->name('profile.update');

    //要望・問い合わせ
    Route::get('request', [RequestController::class, 'index'])->name('request.index');
    Route::post('request/send', [RequestController::class, 'send'])->name('request.send');

    //-------------------------------------------------------------------------------------------------------
    //メールアドレス認証用ルート
    //-------------------------------------------------------------------------------------------------------
    // メール認証通知ページ
    Route::get('/email/verify', [VerificationController::class, 'show'])->name('verification.notice');
    // 確認メール送信
    Route::post('/email/verify-send', [VerificationController::class, 'send'])->name('verification.send')
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
    Route::get('friendlist', [FriendlistController::class, 'index'])->name('friend.index');
    //フレンド情報表示
    Route::get('friend/{id}', [FriendlistController::class, 'show'])->name('friend.show');
    //フレンド申請
    Route::post('friend/request', [FriendlistController::class, 'store'])->name('friend.request');
    //フレンド承認
    Route::post('friend/accept', [FriendlistController::class, 'accept'])->name('friend.accept');
    //フレンド申請拒否
    Route::post('friend/decline', [FriendlistController::class, 'decline'])->name('friend.decline');
    //フレンド申請キャンセル
    Route::post('friend/cancel', [FriendlistController::class, 'cancel'])->name('friend.cancel');

    //-------------------------------------------------------------------------------------------------------
    //スマートリモコンリスト表示
    //-------------------------------------------------------------------------------------------------------
    //スマートリモコン一覧　デバイス一覧
    Route::get('smart-remote', [SmartRemoteController::class, 'index'])->name('remote.index');
    //スマートリモコン詳細
    Route::get('smart-remote/{id}', [SmartRemoteController::class, 'show'])->name('remote.show');
    //スマートリモコン登録
    Route::post('smart-remote', [SmartRemoteController::class, 'store'])->name('remote.store');
    //スマートリモコン詳細変更
    Route::post('smart-remote/update', [SmartRemoteController::class, 'update'])->name('remote.update');
    //スマートリモコン削除
    Route::post('smart-remote/destroy', [SmartRemoteController::class, 'destroy'])->name('remote.destroy');
    //スマートリモコン共有解除
    Route::post('smart-remote/unshare', [SmartRemoteController::class, 'unshare'])->name('remote.unshare');

    //-------------------------------------------------------------------------------------------------------
    //デバイス詳細
    //-------------------------------------------------------------------------------------------------------
    //デバイス詳細
    Route::get('iotdevice/{id}', [IotDeviceController::class, 'show'])->name('iotdevice.show');
    //デバイス登録
    Route::post('iotdevice/activate', [IotDeviceController::class, 'activate'])->name('iotdevice.activate');
    //デバイス詳細変更
    Route::post('iotdevice/update', [IotDeviceController::class, 'update'])->name('iotdevice.update');
    //スマートリモコン削除
    Route::post('iotdevice/destroy', [IotDeviceController::class, 'destroy'])->name('iotdevice.destroy');

    //音声登録
    Route::post('iotdevice/voice-print', [IotDeviceController::class, 'set_voice_print'])->name('iotdevice.set_voice_print');
    //音声スコアチェック
    Route::post('iotdevice/voice-score-check', [IotDeviceController::class, 'voice_score_check'])->name('iotdevice.voice_score_check');

    //-------------------------------------------------------------------------------------------------------
    //メモ
    //-------------------------------------------------------------------------------------------------------
    //メモ一覧
    Route::get('note', [NoteController::class, 'index'])->name('note.index');
    //メモ詳細
    Route::get('note/{id}', [NoteController::class, 'show'])->name('note.show');
    //メモ登録
    Route::post('note', [NoteController::class, 'store'])->name('note.store');
    //メモ詳細変更
    Route::post('note/update', [NoteController::class, 'update'])->name('note.update');
    //メモ削除
    Route::post('note/destroy', [NoteController::class, 'destroy'])->name('note.destroy');
    //メモ共有　API経由で実施
    //メモ共有解除
    Route::post('note/unshare', [NoteController::class, 'unshare'])->name('note.unshare');

});


//-------------------------------------------------------------------------------------------------------
// 管理者権限必須
//-------------------------------------------------------------------------------------------------------
Route::middleware(['auth', 'verified', AdminMiddleware::class])->group(function () {
    Route::group(['prefix' => 'admin'], function(){

        Route::get('home', [AdminHomeController::class, 'home'])->name('admin.home');

        //----------------------------------------------------------------------------------
        //ユーザー
        //----------------------------------------------------------------------------------
        //一覧
        Route::get('user/search', [AdminUserController::class, 'index'])->name('admin.user.index');
        Route::post('user/update', [AdminUserController::class, 'update'])->name('admin.user.update');
        //依頼・要望
        Route::get('user/request/search', [AdminRequestController::class, 'index'])->name('admin.user.request.index');
        Route::post('user/request/update', [AdminRequestController::class, 'update'])->name('admin.user.request.update');
        //----------------------------------------------------------------------------------


        //----------------------------------------------------------------------------------
        //IoTデバイス
        //----------------------------------------------------------------------------------
        //デバイス一覧
        Route::get('iotdevice/search', [AdminIotDeviceController::class, 'index'])->name('admin.iotdevice.index');
        //デバイス登録
        //Route::get('iotdevice/create', [AdminIotDeviceController::class, 'create'])->name('admin.iotdevice.create');
        //Route::post('iotdevice/store', [AdminIotDeviceController::class, 'store'])->name('admin.iotdevice.store');
        //デバイス検索>変更
        Route::post('iotdevice/update', [AdminIotDeviceController::class, 'update'])->name('admin.iotdevice.update');
        //デバイス検索>削除
        Route::post('iotdevice/destroy', [AdminIotDeviceController::class, 'destroy'])->name('admin.iotdevice.destroy');
        //----------------------------------------------------------------------------------


        //----------------------------------------------------------------------------------
        //リモコン
        //----------------------------------------------------------------------------------
        //リモコンデザイン一覧
        Route::get('virtualremote-blade/search', [AdminSmartRemoteController::class, 'index'])->name('admin.virtualremote.blade.index');
        //リモコンデザイン登録
        Route::get('virtualremote-blade/create', [AdminSmartRemoteController::class, 'create'])->name('admin.virtualremote.blade.create');
        Route::post('virtualremote-blade/store', [AdminSmartRemoteController::class, 'store'])->name('admin.virtualremote.blade.store');
        //リモコンデザイン検索>変更
        Route::post('virtualremote-blade/update', [AdminSmartRemoteController::class, 'update'])->name('admin.virtualremote.blade.update');
        //リモコンデザイン検索>削除
        Route::post('virtualremote-blade/destroy', [AdminSmartRemoteController::class, 'destroy'])->name('admin.virtualremote.blade.destroy');
        //リモコンデザインチェック
        Route::get('virtualremote-blade/preview', [AdminSmartRemoteController::class, 'preview'])->name('admin.virtualremote.blade.preview');
        //----------------------------------------------------------------------------------

        
        //----------------------------------------------------------------------------------
        //広告
        //----------------------------------------------------------------------------------
        //検索
        Route::get('adv/search', [AdminAdvController::class, 'index'])->name('admin.adv.index');
        //登録
        Route::get('adv/create', [AdminAdvController::class, 'create'])->name('admin.adv.create');
        Route::post('adv/store', [AdminAdvController::class, 'store'])->name('admin.adv.store');
        //検索>変更
        Route::post('adv/update', [AdminAdvController::class, 'update'])->name('admin.adv.update');
        //検索>削除
        Route::post('adv/destroy', [AdminAdvController::class, 'destroy'])->name('admin.adv.destroy');
        //広告設定
        Route::get('adv/config', [AdminAdvController::class, 'config'])->name('admin.adv.config');
        Route::post('adv/config', [AdminAdvController::class, 'config_update'])->name('admin.adv.config.update');
        //----------------------------------------------------------------------------------

        
        //----------------------------------------------------------------------------------
        //通知
        //----------------------------------------------------------------------------------
        Route::get('notification/search', [AdminNotificationController::class, 'index'])->name('admin.notification.index');
        //メール通知
        Route::post('notification/mail', [AdminNotificationController::class, 'admin_mail_send'])->name('admin.mail.send');
        //プッシュ通知
        Route::post('notification/push', [AdminNotificationController::class, 'admin_push_send'])->name('admin.push.send');
        //----------------------------------------------------------------------------------


        //----------------------------------------------------------------------------------
        //ゲーム（汎用プラットフォーム制御）
        //----------------------------------------------------------------------------------
        // ゲーム一覧・基本情報
        Route::get('game/common/search', [AdminGameController::class, 'game_index'])->name('admin.game.index');
        Route::post('game/common/update', [AdminGameController::class, 'game_update'])->name('admin.game.update');
        Route::post('game/common/destroy', [AdminGameController::class, 'game_destroy'])->name('admin.game.destroy');
        Route::get('game/master/publish/{gameKey}/{type?}/{targetKey?}', [AdminGameController::class, 'publishGame'])->name('admin.game.publish');

        // キャラクター管理・職人部屋
        Route::get('game/character/search', [AdminGameCharacterController::class, 'character_index'])->name('admin.game.character.index');
        Route::post('game/character/update', [AdminGameCharacterController::class, 'character_update'])->name('admin.game.character.update');
        Route::get('game/asset', [AdminGameCharacterController::class, 'asset_index'])->name('admin.game.asset.index');
        Route::post('game/asset/update', [AdminGameCharacterController::class, 'asset_update'])->name('admin.game.asset.update');

        // マップ管理
        Route::get('game/map/search', [AdminGameMapController::class, 'map_index'])->name('admin.game.map.index');
        Route::post('game/map/update', [AdminGameMapController::class, 'map_update'])->name('admin.game.map.update');
        Route::post('game/map/destroy', [AdminGameMapController::class, 'map_destroy'])->name('admin.game.map.destroy');

        // ステージ管理
        Route::get('game/stage/search', [AdminGameStageController::class, 'stage_index'])->name('admin.game.stage.index');
        Route::post('game/stage/update', [AdminGameStageController::class, 'stage_update'])->name('admin.game.stage.update');

        // 武器・アイテム管理
        Route::get('game/item/search', [AdminGameItemController::class, 'item_index'])->name('admin.game.item.index');
        Route::post('game/item/update', [AdminGameItemController::class, 'item_update'])->name('admin.game.item.update');

        // スプライトシート管理（画像倉庫・切り出し定義）
        Route::get('game/sprite-sheet', [AdminGameAssetController::class, 'sprite_sheet_index'])->name('admin.game.sprite_sheet.index');
        Route::get('game/pixel-parts', [AdminGameAssetController::class, 'pixel_parts_index'])->name('admin.game.pixel_parts.index');
        Route::get('game/grid-parts', [AdminGameAssetController::class, 'grid_parts_index'])->name('admin.game.grid_parts.index');
        
        Route::post('game/sprite-sheet/upload', [AdminGameAssetController::class, 'sprite_sheet_upload'])->name('admin.game.sprite_sheet.upload');
        Route::post('game/sprite-sheet/update', [AdminGameAssetController::class, 'sprite_sheet_update'])->name('admin.game.sprite_sheet.update');
        Route::post('game/sprite-sheet/destroy', [AdminGameAssetController::class, 'sprite_sheet_destroy'])->name('admin.game.sprite_sheet.destroy');
        Route::post('game/sprite-sheet/rename', [AdminGameAssetController::class, 'sprite_sheet_rename'])->name('admin.game.sprite_sheet.rename');


        //----------------------------------------------------------------------------------
        //その他
        //----------------------------------------------------------------------------------
        //メモ検索
        Route::get('another/memo/search', [AdminAnotherController::class, 'index'])->name('admin.memo.index');
        //検索>登録
        Route::post('another/memo/search/store', [AdminAnotherController::class, 'store'])->name('admin.memo.store');
        //検索>変更
        Route::post('another/memo/search/update', [AdminAnotherController::class, 'update'])->name('admin.memo.update');
        //検索>削除
        Route::post('another/memo/search/destroy', [AdminAnotherController::class, 'destroy'])->name('admin.memo.destroy');
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