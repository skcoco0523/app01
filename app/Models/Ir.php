<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\IotDevice;
use App\Models\VirtualRemote;
use App\Models\VirtualRemoteUser;
use App\Models\IotDeviceSignal;
use App\Models\Mosquitto;
use Exception;

class Ir extends Model
{
    /**
     * 赤外線信号送信処理を一元管理する
     *
     * @param array $params
     * @return array ['success' => bool, 'msg' => string]
     */
    public static function sendSignal(array $params): array
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "------- start -------");
        make_error_log($error_log, "params: " . print_r($params, true));

        try {
            $userId   = $params['user_id'] ?? null;
            $remoteId = $params['remote_id'] ?? null;
            $deviceId = $params['device_id'] ?? null;

            // テスト送信フラグ判定 (1 / true / '1' 対応)
            $testFlag = filter_var($params['test_flag'] ?? false, FILTER_VALIDATE_BOOLEAN) || (($params['test_flag'] ?? 0) == 1);

            // 1. ユーザーIDの必須チェック
            if (!$userId) {
                make_error_log($error_log, "Error: user_id が指定されていません。");
                return ['success' => false, 'msg' => 'ユーザーIDが指定されていません。'];
            }

            $device      = null;
            $mqttPayload = [];

            // ==================================================================
            // パターン1: テスト送信処理 (test_flag = true)
            // リモコン未作成・学習中のため remote_id やリモコン権限チェックは行わない
            // ==================================================================
            if ($testFlag) {
                make_error_log($error_log, "Processing: Test IR Send");

                if (!$deviceId || (int)$deviceId === 0) {
                    make_error_log($error_log, "Error: テスト送信対象の device_id が指定されていません。");
                    return ['success' => false, 'msg' => 'テスト送信対象のデバイスIDが指定されていません。'];
                }

                // 操作ユーザーが管理所有権を持つ対象デバイスを取得
                $device = IotDevice::getIotDeviceList(1, false, null, [
                    'search_id'        => $deviceId,
                    'search_admin_uid' => $userId
                ])->first();

                if (!$device) {
                    make_error_log($error_log, "Error: テスト送信対象デバイス(ID:{$deviceId})が存在しないか権限がありません。");
                    return ['success' => false, 'msg' => '対象デバイスが見つからないか、権限がありません。'];
                }

                if (empty($device->receive_data)) {
                    make_error_log($error_log, "Error: 一時受信データ(receive_data)が存在しません。");
                    return ['success' => false, 'msg' => 'テスト送信可能な受信用一時データが存在しません。再度受信待機を行ってください。'];
                }

                // RAW波形データのパケット生成
                $dataArray = json_decode($device->receive_data, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($dataArray) || !isset($dataArray['raw'])) {
                    make_error_log($error_log, "Error: 受信一時データのJSONデコードに失敗しました。");
                    return ['success' => false, 'msg' => 'テスト受信データの解析に失敗しました。'];
                }

                $mqttPayload = [
                    'type' => 'raw',
                    'raw'  => $dataArray['raw'],
                    'freq' => $dataArray['freq'] ?? $dataArray['kHz'] ?? 38
                ];

            // ==================================================================
            // パターン2: 通常送信処理 (test_flag = false)
            // remote_id 必須、リモコンの共有・所有権チェックを行う
            // ==================================================================
            } else {
                if (!$remoteId) {
                    make_error_log($error_log, "Error: remote_id が指定されていません。");
                    return ['success' => false, 'msg' => 'リモコンIDが指定されていません。'];
                }

                // 共有リモコン対応：操作ユーザー(user_id)がこのリモコン(remote_id)の利用権限を持っているか検証
                $vRemoteUser = VirtualRemoteUser::getVirtualRemoteUserList(1, true, false, [
                    'search_remote_id' => $remoteId,
                    'search_user_id'   => $userId,
                ])->first();

                if (!$vRemoteUser) {
                    make_error_log($error_log, "Error: 指定されたリモコンが存在しないか操作権限がありません。remote_id:{$remoteId}, user_id:{$userId}");
                    return ['success' => false, 'msg' => '指定されたリモコンが存在しないか、アクセス権限がありません。'];
                }

                // リモコン本体（VirtualRemote）モデルの取得
                $vRemote = VirtualRemote::find($remoteId);
                if (!$vRemote) {
                    make_error_log($error_log, "Error: 仮想リモコン本体データが存在しません。remote_id:{$remoteId}");
                    return ['success' => false, 'msg' => 'リモコン本体データが見つかりません。'];
                }

                $libraryFlag = isset($params['library_flag']) ? (int)$params['library_flag'] : (int)$vRemote->library_flag;

                // --------------------------------------------------------------
                // 2-A. ライブラリ型リモコン
                // --------------------------------------------------------------
                if ($libraryFlag === 1) {
                    make_error_log($error_log, "Processing: Library-based IR control");

                    $protocol = $params['protocol'] ?? ($vRemote->protocol ?? null);
                    if (empty($protocol)) {
                        make_error_log($error_log, "Error: ライブラリ型送信に必要な protocol が未設定です。remote_id: {$remoteId}");
                        return ['success' => false, 'msg' => '送信プロトコルが設定されていません。リモコン設定を確認してください。'];
                    }

                    $targetDeviceId = ($deviceId && (int)$deviceId > 0) ? $deviceId : ($vRemote->device_id ?? null);
                    if (!$targetDeviceId || (int)$targetDeviceId === 0) {
                        make_error_log($error_log, "Error: ライブラリ型リモコン(ID:{$remoteId})に送信先デバイスが紐づいていません。");
                        return ['success' => false, 'msg' => '送信先デバイスが設定されていません。リモコン設定画面からデバイスを選択してください。'];
                    }

                    $device = IotDevice::getIotDeviceList(1, false, null, [
                        'search_id' => $targetDeviceId,
                    ])->first();

                    if (!$device) {
                        make_error_log($error_log, "Error: 送信対象デバイス(ID:{$targetDeviceId})が存在しません。");
                        return ['success' => false, 'msg' => '送信対象デバイスが見つかりません。'];
                    }

                    $mqttPayload = [
                        'type'     => 'library',
                        'protocol' => $protocol,
                    ];

                    $settings  = $params['settings'] ?? [];
                    $paramKeys = ['temp', 'mode', 'fan', 'swingv', 'clean', 'power'];
                    foreach ($paramKeys as $key) {
                        if (array_key_exists($key, $params)) {
                            $settings[$key] = $params[$key];
                        }
                    }

                    if (!empty($settings)) {
                        if (isset($settings['temp']))   $mqttPayload['temp']   = (float)$settings['temp'];
                        if (isset($settings['mode']))   $mqttPayload['mode']   = $settings['mode'];
                        if (isset($settings['fan']))    $mqttPayload['fan']    = $settings['fan'];
                        if (isset($settings['swingv'])) $mqttPayload['swingv'] = $settings['swingv'];
                        if (isset($settings['clean']))  $mqttPayload['clean']  = filter_var($settings['clean'], FILTER_VALIDATE_BOOLEAN);
                        if (isset($settings['power']))  $mqttPayload['power']  = filter_var($settings['power'], FILTER_VALIDATE_BOOLEAN);

                        $currentSettings = $vRemote->settings ?? [];
                        if (is_string($currentSettings)) {
                            $currentSettings = json_decode($currentSettings, true) ?? [];
                        }
                        if (!is_array($currentSettings)) {
                            $currentSettings = [];
                        }

                        $vRemote->settings = array_merge($currentSettings, $settings);
                        $vRemote->save();
                        make_error_log($error_log, "Updated VirtualRemote settings: " . json_encode($vRemote->settings));
                    }

                    if (isset($params['hex']) && $params['hex'] !== '') {
                        $mqttPayload['hex']  = $params['hex'];
                        $mqttPayload['bits'] = (int)($params['bits'] ?? 32);
                    }

                // --------------------------------------------------------------
                // 2-B. 学習型リモコン
                // --------------------------------------------------------------
                } else {
                    make_error_log($error_log, "Processing: Learned RAW IR control");

                    $signal    = null;
                    $signalId  = $params['signal_id'] ?? null;
                    $buttonNum = $params['button_num'] ?? null;
                    $action    = $params['action'] ?? null;

                    if ($signalId) {
                        $signal = IotDeviceSignal::where('remote_id', $remoteId)->where('id', $signalId)->first();
                    } elseif ($buttonNum) {
                        $signal = IotDeviceSignal::where('remote_id', $remoteId)->where('button_num', $buttonNum)->first();
                    } elseif ($action) {
                        $signal = IotDeviceSignal::where('remote_id', $remoteId)->where('signal_name', $action)->first();
                    }

                    if (!$signal) {
                        make_error_log($error_log, "Error: 信号データが存在しません。remote_id:{$remoteId}, button_num:{$buttonNum}, action:{$action}");
                        return ['success' => false, 'msg' => '指定されたボタンの赤外線信号データが登録されていません。'];
                    }

                    if (empty($signal->signal_data)) {
                        make_error_log($error_log, "Error: 信号波形(signal_data)が空です。signal_id:{$signal->id}");
                        return ['success' => false, 'msg' => '登録されている赤外線波形データが空です。再学習を行ってください。'];
                    }

                    $targetDeviceId = ($deviceId && (int)$deviceId > 0) ? $deviceId : ($signal->device_id ?? null);
                    if (!$targetDeviceId || (int)$targetDeviceId === 0) {
                        make_error_log($error_log, "Error: 信号データ(ID:{$signal->id})に送信先デバイスIDが記録されていません。");
                        return ['success' => false, 'msg' => 'このボタン信号に送信先デバイスが紐づいていません。再学習を行ってください。'];
                    }

                    $device = IotDevice::getIotDeviceList(1, false, null, [
                        'search_id' => $targetDeviceId,
                    ])->first();

                    if (!$device) {
                        make_error_log($error_log, "Error: 送信対象デバイス(ID:{$targetDeviceId})が存在しません。");
                        return ['success' => false, 'msg' => '送信対象デバイスが見つかりません。'];
                    }

                    $rawSignal = $signal->signal_data;

                    $dataArray = json_decode($rawSignal, true);
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($dataArray) || !isset($dataArray['raw'])) {
                        make_error_log($error_log, "Error: RAW波形のJSONデコードに失敗しました。");
                        return ['success' => false, 'msg' => '赤外線波形データの解析に失敗しました。'];
                    }

                    $mqttPayload = [
                        'type' => 'raw',
                        'raw'  => $dataArray['raw'],
                        'freq' => $dataArray['freq'] ?? $dataArray['kHz'] ?? 38
                    ];
                }
            }

            // ==================================================================
            // 3. MQTT 送信実行
            // ==================================================================
            if ($device && !empty($mqttPayload)) {
                make_error_log($error_log, "Target mac_addr: " . $device->mac_addr);
                make_error_log($error_log, "Publishing MQTT ir-send payload: " . json_encode($mqttPayload));
                return Mosquitto::publishMQTT($device->mac_addr, 'ir-send', $mqttPayload);
            }

            return ['success' => false, 'msg' => '赤外線送信パケットの作成に失敗しました。'];

        } catch (Exception $e) {
            make_error_log($error_log, "Exception occurred: " . $e->getMessage());
            return ['success' => false, 'msg' => '送信処理中にシステムエラーが発生しました: ' . $e->getMessage()];
        }
    }
}