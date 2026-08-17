<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//use App\Http\Controllers\Auth\ApiLoginController;
//use App\Http\Controllers\Api\ApiPlaylistController;
use App\Http\Controllers\Api\ApiAdvController;
use App\Http\Controllers\Api\ApiSmartRemoteController;
use App\Http\Controllers\Api\ApiFriendlistController;
use App\Http\Controllers\Api\ApiNoteController;
use App\Http\Controllers\Api\ApiGameController; // Added for game data API
use App\Http\Controllers\Api\ApiAudioController;
use App\Http\Controllers\Api\ApiIotDeviceController;

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

    // デバイス疎通確認リクエスト (Ping)
    Route::get('/iot_device_ping', [ApiSmartRemoteController::class, 'api_iot_device_ping']);

    // 赤外線受信待機リクエスト
    Route::get('/ir-receive-request', [ApiSmartRemoteController::class, 'api_ir_receive_request']);
    // デバイスステータス取得
    Route::get('/iot_device_status/get', [ApiSmartRemoteController::class, 'api_iot_device_status_get']);
    // 赤外線信号保存
    Route::post('/smart-remote/signal/save', [ApiSmartRemoteController::class, 'api_ir_signal_save']);
    // 赤外線信号送信
    Route::post('/smart-remote/signal/send', [ApiSmartRemoteController::class, 'api_ir_send']);

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

// Game Data API (Publicly accessible)
// These endpoints serve static JSON files published by the admin panel.
Route::prefix('games/{gameKey}')->group(function () {
    Route::get('atlas/get', [ApiGameController::class, 'api_game_atlas_get']);
    Route::get('characters/get', [ApiGameController::class, 'api_game_characters_get']);
    Route::get('character/get/{characterKey}', [ApiGameController::class, 'api_game_character_get']);
    Route::get('stages/get', [ApiGameController::class, 'api_game_stages_get']);
    Route::get('weapons/get', [ApiGameController::class, 'api_game_weapons_get']);
    Route::get('items/get', [ApiGameController::class, 'api_game_items_get']);
});

// 音声データ受信用API
Route::post('/audio/upload', [ApiAudioController::class, 'api_audio_upload']);

