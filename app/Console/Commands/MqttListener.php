<?php
//「php artisan mqtt:listen」を実行してMQTTメッセージをリッスンする

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\ConnectionSettings;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\Mosquitto; 
use App\Models\IotDevice; 


class MqttListener extends Command
{
    protected $signature = 'mqtt:listen';
    protected $description = 'Listen for MQTT messages for new device registration.';

    public function handle()
    {
        $error_log = "subscribeMQTT.log";
        make_error_log($error_log,"--------setup start---------");

        $server   = env('MQTT_BROKER_HOST', 'localhost');
        $port     = env('MQTT_BROKER_PORT', 8883); // AWS IoTは通常8883
        $clientId = env('MQTT_CLIENT_ID', 'laravel_mqtt_listener');
        $clientId .= "-listener"; // リスナー用にクライアントIDを変更
        
        // AWS IoT Core接続用の設定を作成
        $settings = (new ConnectionSettings())
            ->setConnectTimeout(10)
            ->setUseTls(true)
            ->setTlsCertificateAuthorityFile(storage_path(env('MQTT_CERT_CA')))
            ->setTlsClientCertificateFile(storage_path(env('MQTT_CERT_CRT')))
            ->setTlsClientCertificateKeyFile(storage_path(env('MQTT_CERT_KEY')));
            
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
            $type           = $data['type'] ?? null;
            $ver            = $data['ver'] ?? null;
            $data           = $data['data'] ?? null;

            $this->info('topic:'. $topic);
            //make_error_log($error_log,"mac_addr:".$mac_addr." type:".$type." type_num:".s$type_num." ver:".$ver." uid:".$uid." data:".$data);
            make_error_log($error_log,"mac_addr:".$mac_addr."   device_name:".$device_name."   command:".$command."   ver:".$ver."   data:".$data);

            
            if(config('common.device_info')[$type]){
                //デバイス起動時の初回アクセス
                if ($command == 'device-access')        $this->mqtt_device_access($mac_addr, $device_name, $ver);
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
        while (true) {
            try {
                make_error_log($error_log, 'Connecting to MQTT broker...');
                //windows環境ではIPv6で接続できない場合があるため、優先設定(prefixpolicies)を変えておく
                $mqtt->connect($settings, true);

                make_error_log($error_log, 'Connected to MQTT broker');

                $mqtt->subscribe('device/#', $subscribe_callback, 0);

                while ($mqtt->isConnected()) {
                    $mqtt->loop(true);
                }

            } catch (\Throwable $e) {
                make_error_log($error_log, 'MQTT error: '.$e->getMessage());
                sleep(5);
            }
        }
        return Command::SUCCESS;
    }

    //デバイス起動時の初回アクセス
    public function mqtt_device_access($mac_addr, $device_name, $ver = null){
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log,"---------------start----------------");
        make_error_log($error_log,"mac_addr:".$mac_addr);
        $device = IotDevice::getIotDeviceList(1,false,NULL,['admin_flag' => true, 'search_addr' => $mac_addr])->first();
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
            //type:99=未設定
            $ret = IotDevice::createIotDevice(["mac_addr" => $mac_addr, "type" => 99, "name" => $device_name, "ver" => $ver, "pincode" => $pincode]);
            if($ret['error_code'] == 0){
                //登録成功　piccodeをESPデバイスに送信
                make_error_log($error_log,"device create success id:".$ret['id']);
                $jdata = json_encode(["pincode" => (String)$pincode]);
                Mosquitto::publishMQTT($mac_addr, "temp_regist", $jdata);
                
            }else{
                //登録失敗
                make_error_log($error_log,"device create error_code:".$ret['error_code']);
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
    public function mqtt_ir_received($mac_addr, $data){
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        make_error_log($error_log,"---------------start----------------");
        make_error_log($error_log,"----------------end-----------------");
    }





}