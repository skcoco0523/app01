<?php
//「php artisan mqtt:listen」を実行してMQTTメッセージをリッスンする

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

use App\Models\Mosquitto; 
use App\Models\IotDevice; 
use App\Models\IotDeviceSignal;


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
            
            //make_error_log($error_log,"mac_addr:".$mac_addr." type:".$type." type_num:".s$type_num." ver:".$ver." uid:".$uid." data:".$data);
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
        // 起動時点のファイル更新日時を取得　変わっていれば終了し再度起動させる
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

                while ($mqtt->isConnected()) {
                    // loop() は内部で無限ループを持つため、1回ごとの受信処理を行う loopOnce() を使用する。
                    // 最大5秒間メッセージを待機（ブロッキング）し、受信するかタイムアウトすると制御を戻す。
                    $mqtt->loopOnce(5);

                    // ファイルの更新をチェック
                    clearstatcache(true, $script_file); 
                    if (filemtime($script_file) !== $last_modified) {
                        $this->info("File changed. Exiting...");
                        make_error_log($error_log, "File changed detected: {$script_file}. Exiting for auto-restart.");

                        // 管理者宛てに通知し処理を強制停止する
                        try {
                            $send_info = new \stdClass();
                            $send_info->title = "MQTT Listener 再起動通知";
                            $send_info->body = "ファイル更新を検知したため、MQTT Listenerを再起動します。";
                            push_send($send_info, null, true); 
                        } catch (\Throwable $e) {
                            make_error_log($error_log, "Notification failed: " . $e->getMessage());
                        }

                        exit; // Systemd (Restart=always) がこのプロセスを再起動させます
                    }
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

        // ステータスを「Online (1)」に更新
        IotDevice::where('mac_addr', $mac_addr)->update(['status' => config('common.iot_device_status.online')]);

        make_error_log($error_log, "----------------end-----------------");
    }

    //赤外線信号スタンバイ通知
    public function mqtt_ir_receive_standby($mac_addr)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log, "---------------start----------------");
        make_error_log($error_log, "mac_addr:" . $mac_addr);

        // ステータスを「Standby (3)」に更新
        IotDevice::where('mac_addr', $mac_addr)->update(['status' => config('common.iot_device_status.ir_standby')]);

        make_error_log($error_log, "----------------end-----------------");
    }

    //赤外線信号受信タイムアウト通知
    public function mqtt_ir_receive_timeout($mac_addr)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log, "---------------start----------------");
        make_error_log($error_log, "mac_addr:" . $mac_addr);

        // ステータスを「Timeout (5)」に更新
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

            //仮登録状態　デバイス接続通知　再起動した場合に備えてpiccodeを再送
            if($device->admin_user_id == null){
                $jdata = json_encode(["pincode" => (String)$device->pincode]);
                Mosquitto::publishMQTT($mac_addr, "temp_regist", $jdata);

            //本登録済み　デバイス接続通知
            }else{
                Mosquitto::publishMQTT($mac_addr, "final_regist");
                // ESP32側のデバイス名や音声指紋がサーバー側と異なる場合は、サーバー側の情報を送信して同期させる
                $jdata = [];
                // デバイス名の同期
                if ($device_name !== (String)$device->name) {
                    $jdata["device_name"] = (String)$device->name;
                }

                // ドメインとアップロードURLを常に同期（本番環境/開発環境の動的対応）
                if (app()->environment('local')) {
                    // ローカル（XAMPP）環境：PCのローカルIP（192.168.x.x）を自動取得
                    $localIp = gethostbyname(gethostname());
                    //$baseUrl = "http://{$localIp}";
                    $baseUrl = "http://{$localIp}/app01/public";
                } else {
                    // 本番環境：既存の config('app.url') を使用
                    $baseUrl = config('app.url');
                }

                $jdata["domain"]     = parse_url($baseUrl, PHP_URL_HOST);
                $jdata["upload_url"] = rtrim($baseUrl, '/') . "/api/audio/upload";
                
                // 差分があれば更新通知を送る（URL追加により常に count($jdata) > 0 となる）
                if (count($jdata) > 0) {
                    Mosquitto::publishMQTT($mac_addr, "update_device", json_encode($jdata));
                }

                //所有者が確定しているため接続通知
                $send_info = new \stdClass();
                $send_info->title = "デバイス接続通知";
                $send_info->body = "[".$device->name. "]が接続されました。";
                $send_info->url = route('iotdevice.show', ['id' => $device->id]);
                push_send($send_info, $device->admin_user_id);
            }

        //未登録デバイス
        }else{
            make_error_log($error_log,"device not found...creating");
            
            //未登録デバイスは、本登録対象を検索するためデバイス名を必須とする
            if (empty($device_name)) {
                make_error_log($error_log,"not found device_name:".$device_name);
                return;
            }
            $pincode = random_int(100000, 999999); // 6文字のランダムな文字列を生成
            // ユニークなpincodeになるまで繰り返す
            while (IotDevice::where('pincode', $pincode)->exists()) { $pincode = random_int(100000, 999999); }
            make_error_log($error_log,"pincode:".$pincode);
            $ret = IotDevice::createIotDevice(["mac_addr" => $mac_addr, "type" => $type, "name" => $device_name, "ver" => $ver, "pincode" => $pincode]);
            //登録成功　piccodeをESPデバイスに送信
            if($ret['success']){
                make_error_log($error_log,"device create success id:".$ret['id']);
                $jdata = json_encode(["pincode" => (String)$pincode]);
                Mosquitto::publishMQTT($mac_addr, "temp_regist", $jdata);
                
            //登録失敗
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

        // 1. mac_addr からデバイスを特定
        $device = IotDevice::where('mac_addr', $mac_addr)->first();
        if (!$device) {
            make_error_log($error_log, "device not found for mac_addr: " . $mac_addr);
            return;
        }

        // $ir_signal は json_decode 済みの配列。そのまま再シリアライズして保存
        // インターフェース定義に基づき、{"raw": [...]} 等が含まれる想定
        $receive_json = is_array($ir_signal) ? json_encode($ir_signal) : $ir_signal;

        // 汎用受信フィールドに保存 (コマンド名とデータ)
        $update_data = [
            'status'          => config('common.iot_device_status.ir_received'), // Received
            'receive_command' => 'ir-received',
            'receive_data'    => $receive_json,
        ];

        $device->update($update_data);
        make_error_log($error_log, "device status and receive_data updated. mac_addr:" . $mac_addr);

        make_error_log($error_log, "----------------end-----------------");
    }
}
