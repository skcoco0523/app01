<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//use App\Http\Controllers\Auth\ApiLoginController;
//use App\Http\Controllers\Api\ApiPlaylistController;
use App\Http\Controllers\Api\ApiAdvController;
use App\Http\Controllers\Api\ApiSmartRemoteController;
use App\Http\Controllers\Api\ApiFriendlistController;
use App\Http\Controllers\Api\ApiNoteController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
/*
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
*/

// ログインエンドポイントを追加
//Route::post('/login', [ApiLoginController::class, 'login']);

// 認証済みユーザー向けルート (Sanctum)
Route::middleware('auth:sanctum')->group(function () {

    //ユーザー情報取得
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    // マイプレイリスト取得
    //Route::get('/myplaylist/get', [ApiPlaylistController::class, 'myplaylist_get']);

    // リモコンデザイン検索
    Route::get('/remote-blade/get', [ApiSmartRemoteController::class, 'api_remote_blade_get']);

    // 所有iotデバイス検索
    Route::get('/iot_devices/get', [ApiSmartRemoteController::class, 'api_iot_devices_get']);

    // フレンドリスト取得
    Route::get('/friendlist/get', [ApiFriendlistController::class, 'api_friendlist_get']);

    // ノート共有登録
    Route::post('/note/share', [ApiNoteController::class, 'api_note_manage'])->defaults('type', 'share');
    Route::post('/note/unshare', [ApiNoteController::class, 'api_note_manage'])->defaults('type', 'unshare');

    // 今後追加する場合も同様
    Route::post('/note/enable-edit', [ApiNoteController::class, 'api_note_manage'])->defaults('type', 'enable_edit');
    Route::post('/note/disable-edit', [ApiNoteController::class, 'api_note_manage'])->defaults('type', 'disable_edit');
});

//未認証ユーザー


// 広告情報取得
Route::get('/adv/get', [ApiAdvController::class, 'api_adv_get']);
Route::post('/adv/click', [ApiAdvController::class, 'api_adv_click']);
Route::get('/adv/config', [ApiAdvController::class, 'api_adv_config']);
