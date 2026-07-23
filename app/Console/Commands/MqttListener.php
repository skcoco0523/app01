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
            $data = json_decode($message, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error("Failed to decode JSON from message. Error: " . json_last_error_msg());
                make_error_log($error_log,"Failed to decode JSON from message. Error: " . json_last_error_msg());
                return;
            }

            $mac_addr       = $data['mac_addr'] ?? null;
            $device_name    = $data['device_name'] ?? null;
            $command        = $data['command'] ?? null;
            //config/common.php で定義されているデバイスタイプを取得
            $type           = $data['type'] ?? null;
            $ver            = $data['ver'] ?? null;
            $data           = $data['data'] ?? null;

            $this->info('topic:'. $topic);
            $this->info('command:'. $command);
            $this->info('data:'. $data);
            $this->info('type:'. $type);
            
            //make_error_log($error_log,"mac_addr:".$mac_addr." type:".$type." type_num:".s$type_num." ver:".$ver." uid:".$uid." data:".$data);
            make_error_log($error_log,"mac_addr:".$mac_addr."   device_name:".$device_name."   command:".$command."   ver:".$ver."   data:".$data);

            
            if(config('common.device_info')[$type]){
                //デバイス起動時の初回アクセス
                if ($command == 'device-access')        $this->mqtt_device_access($mac_addr, $device_name, $type, $ver);
                //赤外線信号スタンバイ通知
                if ($command == 'ir-receive-standby')   $this->mqtt_ir_receive_standby($mac_addr);
                //赤外線信号受信通知
                if ($command == 'ir-received')          $this->mqtt_ir_received($mac_addr, $data);
                
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

    //デバイス起動時の初回アクセス
    public function mqtt_device_access($mac_addr, $device_name, $type, $ver = null){
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log,"---------------start----------------");
        make_error_log($error_log,"mac_addr:".$mac_addr);
        $device = IotDevice::getIotDeviceList(1,false,NULL,['admin_flag' => true, 'search_mac_addr' => $mac_addr])->first();
        if ($device !== null) {
            //登録済みデバイス
            make_error_log($error_log,"device registered...mac_addr:".$device->mac_addr);

            if($device->admin_user_id == null){
                //仮登録済み　デバイス接続通知　再起動した場合に備えてpiccodeを再送
                $jdata = json_encode(["pincode" => (String)$device->pincode]);
                Mosquitto::publishMQTT($mac_addr, "temp_regist", $jdata);

            }else{
                //本登録済み　デバイス接続通知
                $jdata = json_encode([
                    "voice_print" => (String)$device->voice_print
                ]);
                Mosquitto::publishMQTT($mac_addr, "final_regist", $jdata);

                //所有者が確定しているため接続通知
                $send_info = new \stdClass();
                $send_info->title = "デバイス接続通知";
                $send_info->body = "[".$device->name. "]が接続されました。";
                $send_info->url = route('iotdevice.show', ['id' => $device->id]);
                push_send($send_info, $device->admin_user_id);
            }

        }else{
            //未登録デバイス
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
            if($ret['success']){
                //登録成功　piccodeをESPデバイスに送信
                make_error_log($error_log,"device create success id:".$ret['id']);
                $jdata = json_encode(["pincode" => (String)$pincode]);
                Mosquitto::publishMQTT($mac_addr, "temp_regist", $jdata);
                
                $send_info = new \stdClass();
                $send_info->title = "IOTデバイス仮登録通知";
                $send_info->body = "新しいIOTデバイスが仮登録されました。";
                push_send($send_info, null, true); 
                
            }else{
                //登録失敗
                make_error_log($error_log,$ret['msg']);
                return;

            }
        }
        make_error_log($error_log,"----------------end-----------------");
    }

    //赤外線信号スタンバイ通知
    public function mqtt_ir_receive_standby($mac_addr){
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log,"---------------start----------------");
        make_error_log($error_log,"----------------end-----------------");
    }

    //赤外線信号受信通知
    public function mqtt_ir_received($mac_addr, $data)
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

        /* 
           信号受信時のフロー（暫定）:
           ESP32が学習モード中に受信した信号を、現在「学習待機中」のリモコンボタンに割り当てる、
           あるいは一旦デフォルトの「未分類(category_name='unknown')」として保存するなどの処理が必要。
           ここでは一旦、引数 $data に必要な情報が含まれている前提、
           あるいはシステム側の「学習中フラグ」を参照する想定。
        */

        // 仮のデータ構造（ESP32からの $data に remote_id や button_num が含まれない場合を考慮）
        $insert_data = [
            'device_id'     => $device->id,
            'remote_id'     => $data['remote_id'] ?? 0, // 本来は学習中のリモコンID
            'button_num'    => $data['button_num'] ?? 0,
            'category_name' => $data['category_name'] ?? '未分類',
            'signal_name'   => $data['signal_name'] ?? '受信信号_' . date('YmdHis'),
            'signal_data'   => is_array($data['signal_data']) ? json_encode($data['signal_data']) : ($data['signal_data'] ?? $data),
        ];

        $ret = IotDeviceSignal::createIotDeviceSignal($insert_data);
        make_error_log($error_log, "Result: " . $ret['msg']);

        make_error_log($error_log, "----------------end-----------------");
    }





}