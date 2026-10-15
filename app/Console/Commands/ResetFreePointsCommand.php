<?php
//「php artisan mqtt:listen」を実行してMQTTメッセージをリッスンする

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

use App\Models\Mosquitto; 
use App\Models\IotDevice; 
use App\Models\User;
use App\Models\CommonConfig;
use App\Services\PointService; // ★追加


class MqttListener extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Listen for MQTT messages for new device registration.';

    public function handle()
    {
        // OPcacheを無効化して常に最新のファイルを読み込む
        ini_set('opcache.enable', '0');

        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"--------setup start---------");

        $server   = config('services.mqtt.host');
        $port     = (int) config('services.mqtt.port');
        $clientId = config('services.mqtt.client_id') . '-listener';

        // AWS IoT Core接続用の設定を作成
        $settings = (new ConnectionSettings())
            ->setConnectTimeout(10)
            ->setUseTls(true)
            ->setTlsCertificateAuthorityFile(storage_path(config('services.mqtt.cert_ca')))
            ->setTlsClientCertificateFile(storage_path(config('services.mqtt.cert_crt')))
            ->setTlsClientCertificateKeyFile(storage_path(config('services.mqtt.cert_key')));
            
        make_error_log($error_log,"server:".$server."  port:".$port."  clientId:".$clientId);
        try {
            $mqtt = new MqttClient($server, $port, $clientId);
        } catch (\Exception $e) {
            make_error_log($error_log, "MqttClient init failed: " . $e->getMessage());
            return;
        }
        
        /*
        device-access： ESPデバイス起動後のアクセス通知
        ir-signal：     IRデバイスからの赤外線信号登録要求
        */

        $subscribe_callback = function ($topic, $message) use ($error_log) {
            make_error_log($error_log,"-------subscribe check--------");
            $topic_array = explode('/', $topic);
            $mac_addr = $topic_array[1] ?? null;

            // JSONペイロードを解析
            $payload = json_decode($message, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Failed to decode JSON from message. Error: " . json_last_error_msg());
                make_error_log($error_log,"Failed to decode JSON from message. Error: " . json_last_error_msg());
                return;
            }

            $mac_addr       = $payload['mac_addr'] ?? null;
            $device_name    = $payload['device_name'] ?? null;
            $command        = $payload['command'] ?? null;
            //config/common.php で定義されているデバイスタイプを取得
            $type           = $payload['type'] ?? null;
            $ver            = $payload['ver'] ?? null;
            $data           = $payload['data'] ?? null;
            $ir_signal      = $payload['ir_signal'] ?? null;

            $this->info('topic:'. $topic);
            $this->info('command:'. $command);
            $this->info('data:'. $data);
            $this->info('type:'. $type);
            
            make_error_log($error_log,"mac_addr:".$mac_addr."   device_name:".$device_name."   command:".$command."   ver:".$ver."   data:".$data);

            
            if(config('common.device_info')[$type]){
                //デバイス起動時の初回アクセス
                if ($command == 'device-access'){
                    $this->info('device_name:'. $device_name);
                    make_error_log($error_log,"device_name:".$device_name);

                    $this->mqtt_device_access($mac_addr, $type, $device_name, $ver);
                }
                //疎通確認応答
                if ($command == 'pong')                 $this->mqtt_device_pong($mac_addr);
                //トークン要求
                if ($command == 'request_token')        $this->mqtt_request_token($mac_addr);
                //赤外線信号受信スタンバイ通知
                if ($command == 'ir-receive-standby')   $this->mqtt_ir_receive_standby($mac_addr);
                //赤外線信号受信タイムアウト通知
                if ($command == 'ir-receive-timeout')   $this->mqtt_ir_receive_timeout($mac_addr);
                //赤外線信号受信通知
                if ($command == 'ir-received'){
                    // make_error_log 用に ir_signal を文字列化
                    $ir_signal_str = is_array($ir_signal) ? json_encode($ir_signal) : $ir_signal;
                    $this->info('ir_signal:'. $ir_signal_str);
                    make_error_log($error_log,"ir_signal_str:".$ir_signal_str);
                    $this->mqtt_ir_received($mac_addr, $ir_signal);
                }

                make_error_log($error_log,"--------end---------");
            }else{
                make_error_log($error_log,"--------type:" . $type . " is undefined ---------");
            }
        };

        // 無限ループで永続的な実行を保証
        $script_file = __FILE__;
        $last_modified = filemtime($script_file);
        while (true) {
            try {
                make_error_log($error_log, 'Connecting to MQTT broker...');
                //windows環境ではIPv6で接続できない場合があるため、優先設定(prefixpolicies)を変えておく
                $mqtt->connect($settings, true);

                make_error_log($error_log, 'Connected to MQTT broker');

                $mqtt->subscribe('device/#', $subscribe_callback, 0);
                
                if($mqtt->isConnected()){

                    //管理者宛てに通知
                    $send_info = new \stdClass();
                    $send_info->title = "MQTT Listener 再起動通知";
                    $send_info->body = "MQTT Listenerを再起動しました。";
                    push_send($send_info, null, true); 
                }

                $last_check_time = time();

                while ($mqtt->isConnected()) {
                    $mqtt->loopOnce(1);

                    // ファイル更新チェック
                    if (time() - $last_check_time >= 60) {
                        $last_check_time = time();

                        clearstatcache(true, $script_file); 
                        if (filemtime($script_file) !== $last_modified) {
                            $this->info("File changed. Exiting...");
                            make_error_log($error_log, "File changed detected: {$script_file}. Exiting for auto-restart.");

                            try {
                                $send_info = new \stdClass();
                                $send_info->title = "MQTT Listener 再起動通知";
                                $send_info->body = "ファイル更新を検知したため、MQTT Listenerを再起動します。";
                                push_send($send_info, null, true); 
                            } catch (\Throwable $e) {
                                make_error_log($error_log, "Notification failed: " . $e->getMessage());
                            }

                            exit; 
                        }
                    }

                    usleep(10000);
                }

            } catch (\Throwable $e) {
                make_error_log($error_log, 'MQTT error: '.$e->getMessage());
                sleep(5);
            }
        }
        return Command::SUCCESS;
    }

    // デバイス疎通確認応答
    public function mqtt_device_pong($mac_addr)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log, "---------------start----------------");
        make_error_log($error_log, "mac_addr:" . $mac_addr);

        IotDevice::where('mac_addr', $mac_addr)->update(['status' => config('common.iot_device_status.online')]);

        make_error_log($error_log, "----------------end-----------------");
    }

    /**
     * serverアクセス用ワンタイムトークン発行要求
     * 音声送信前のポイント判定・同期処理を含みます
     */
    public function mqtt_request_token($mac_addr)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log, "---------------start----------------");
        make_error_log($error_log, "mac_addr:" . $mac_addr);

        if (empty($mac_addr)) {
            make_error_log($error_log, "mac_addr is empty");
            return;
        }

        // 1. デバイスおよび管理者ユーザーの取得
        $deviceList = IotDevice::getIotDeviceList(1, false, null, ['admin_flag' => true, 'search_mac_addr' => $mac_addr]);
        $device     = (is_array($deviceList) || $deviceList->isEmpty()) ? null : $deviceList->first();

        $user = ($device && !empty($device->admin_user_id)) ? User::find($device->admin_user_id) : null;

        $shouldSend = true;
        // テストモード時は、検証環境から送信されるため、本番環境では管理者には送らない
        $conf_data  = CommonConfig::getValues(['stop_admin_api_token']);
        $stop_flag = (bool)($conf_data['stop_admin_api_token']->value1 ?? false);
        if($stop_flag){
            $isAdmin = $user ? (bool)$user->admin_flag : false;
            $shouldSend = !$isAdmin;
        }

        if ($shouldSend) {
            if (!$user) {
                make_error_log($error_log, "Error: Device or User not found for mac_addr: " . $mac_addr);
                $jdata = json_encode([
                    "token"       => null,
                    "status"      => "error",
                    "free_point"  => 0,
                    "pay_point"   => 0,
                    "total_point" => 0,
                ]);
                Mosquitto::publishMQTT($mac_addr, "server_token", $jdata);
                return;
            }

            // ★ 2. PointService を使用してポイント残高を事前に確認
            $pointService = app(PointService::class);
            $check = $pointService->check($user, 1); // 1pt以上の残高があるかチェック

            if (!$check['can_use']) {
                make_error_log($error_log, "Point Depleted: User {$user->id}. Free: {$check['free_point']}, Pay: {$check['pay_point']}");

                // ポイント不足時：トークンは null、status に "empty" をセットして即返却
                $jdata = json_encode([
                    "token"       => null,
                    "status"      => "empty",
                    "free_point"  => $check['free_point'],
                    "pay_point"   => $check['pay_point'],
                    "total_point" => $check['total_point'],
                ]);

                Mosquitto::publishMQTT($mac_addr, "server_token", $jdata);
                make_error_log($error_log, "----------------end-----------------");
                return;
            }

            // 3. ポイントありの場合：ワンタイムトークンを生成
            $nonce      = \Illuminate\Support\Str::random(16);
            $raw_string = $mac_addr . '_' . microtime(true) . '_' . $nonce;
            $token      = hash_hmac('sha256', $raw_string, config('app.key'));

            \Illuminate\Support\Facades\Cache::put("audio_upload_token_{$mac_addr}", $token, 30);

            make_error_log($error_log, "generated_token:" . $token);

            // 4. ESP32側へ token、status ("ok")、および最新ポイント残高をレスポンス
            $jdata = json_encode([
                "token"       => $token,
                "status"      => "ok",
                "free_point"  => $check['free_point'],
                "pay_point"   => $check['pay_point'],
                "total_point" => $check['total_point'],
            ]);

            Mosquitto::publishMQTT($mac_addr, "server_token", $jdata);
        }

        make_error_log($error_log, "----------------end-----------------");
    }

    //赤外線信号スタンバイ通知
    public function mqtt_ir_receive_standby($mac_addr)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log, "---------------start----------------");
        make_error_log($error_log, "mac_addr:" . $mac_addr);

        IotDevice::where('mac_addr', $mac_addr)->update(['status' => config('common.iot_device_status.ir_standby')]);

        make_error_log($error_log, "----------------end-----------------");
    }

    //赤外線信号受信タイムアウト通知
    public function mqtt_ir_receive_timeout($mac_addr)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log, "---------------start----------------");
        make_error_log($error_log, "mac_addr:" . $mac_addr);

        IotDevice::where('mac_addr', $mac_addr)->update(['status' => config('common.iot_device_status.ir_timeout')]);

        make_error_log($error_log, "----------------end-----------------");
    }

    //デバイス起動時の初回アクセス
    public function mqtt_device_access($mac_addr, $type, $device_name, $ver = null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log,"---------------start----------------");
        make_error_log($error_log,"mac_addr:".$mac_addr);
        $device = IotDevice::getIotDeviceList(1,false,NULL,['admin_flag' => true, 'search_mac_addr' => $mac_addr])->first();
        //登録済みデバイス
        if ($device !== null) {
            make_error_log($error_log,"device registered...mac_addr:".$device->mac_addr);

            //仮登録状態 デバイス接続通知 再起動した場合に備えてpiccodeを再送
            if($device->admin_user_id == null){
                $jdata = json_encode(["pincode" => (String)$device->pincode]);
                Mosquitto::publishMQTT($mac_addr, "temp_regist", $jdata);

            //本登録済み デバイス接続通知
            }else{

                $shouldSend = true;
                $conf_data  = CommonConfig::getValues(['stop_admin_connect_notify']);
                $stop_flag = (bool)($conf_data['stop_admin_connect_notify']->value1 ?? false);
                if($stop_flag){
                    $user    = ($device && !empty($device->admin_user_id)) ? User::find($device->admin_user_id) : null;
                    $isAdmin = $user ? (bool)$user->admin_flag : false;
                    $shouldSend = !$isAdmin;
                }

                if ($shouldSend) {
                    Mosquitto::publishMQTT($mac_addr, "final_regist");
                    $jdata = [];
                    if ($device_name !== (String)$device->name) {
                        $jdata["device_name"] = (String)$device->name;
                    }

                    if (app()->environment('local')) {
                        $localIp = gethostbyname(gethostname());
                        $baseUrl = "http://{$localIp}/app01/public";
                    } else {
                        $baseUrl = config('app.url');
                    }

                    $jdata["domain"]     = parse_url($baseUrl, PHP_URL_HOST);
                    $jdata["upload_url"] = rtrim($baseUrl, '/') . "/api/audio/upload";
                    
                    if (count($jdata) > 0) {
                        Mosquitto::publishMQTT($mac_addr, "update_device", json_encode($jdata));
                    }

                    $send_info = new \stdClass();
                    $send_info->title = "デバイス接続通知";
                    $send_info->body = "[".$device->name. "]が接続されました。";
                    $send_info->url = route('iotdevice.show', ['id' => $device->id]);
                    push_send($send_info, $device->admin_user_id);
                }
            }

        //未登録デバイス
        }else{
            make_error_log($error_log,"device not found...creating");
            
            if (empty($device_name)) {
                make_error_log($error_log,"not found device_name:".$device_name);
                return;
            }
            $pincode = random_int(100000, 999999);
            while (IotDevice::where('pincode', $pincode)->exists()) { $pincode = random_int(100000, 999999); }
            make_error_log($error_log,"pincode:".$pincode);
            $ret = IotDevice::createIotDevice(["mac_addr" => $mac_addr, "type" => $type, "name" => $device_name, "ver" => $ver, "pincode" => $pincode]);
            if($ret['success']){
                make_error_log($error_log,"device create success id:".$ret['id']);
                $jdata = json_encode(["pincode" => (String)$pincode]);
                Mosquitto::publishMQTT($mac_addr, "temp_regist", $jdata);
            }else{
                make_error_log($error_log,"device create error:".$ret['msg']);
                return;
            }
        }
        make_error_log($error_log,"----------------end-----------------");
    }

    //赤外線信号受信通知
    public function mqtt_ir_received($mac_addr, $ir_signal)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "---------------start----------------");
        make_error_log($error_log, "mac_addr:" . $mac_addr);

        $device = IotDevice::where('mac_addr', $mac_addr)->first();
        if (!$device) {
            make_error_log($error_log, "device not found for mac_addr: " . $mac_addr);
            return;
        }

        $receive_json = is_array($ir_signal) ? json_encode($ir_signal) : $ir_signal;

        $update_data = [
            'status'          => config('common.iot_device_status.ir_received'),
            'receive_command' => 'ir-received',
            'receive_data'    => $receive_json,
        ];

        $device->update($update_data);
        make_error_log($error_log, "device status and receive_data updated. mac_addr:" . $mac_addr);

        make_error_log($error_log, "----------------end-----------------");
    }
}