<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PhpMqtt\Client\MqttClient;  //MQTT
use PhpMqtt\Client\ConnectionSettings;  //MQTT

use Illuminate\Support\Facades\Auth;

class Mosquitto extends Model
{
    use HasFactory;

    /**
     * 外部deviceにMQTTでメッセージ送信
     * 
     * @param string $mac_addr
     * @param string $command
     * @param mixed $data
     * @return array ['success' => bool, 'msg' => string]
     */
    public static function publishMQTT($mac_addr, $command, $data = null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "------- start -------");

        $host     = config('services.mqtt.host', 'localhost');
        $port     = (int) config('services.mqtt.port', 1883);
        $topic    = "web" . '/' . $mac_addr;
        
        $jdata    = [
            'command' => (string)$command,
            'data'    => $data,
        ];
        $json_message = json_encode($jdata);

        make_error_log($error_log, "topic:" . $topic);
        make_error_log($error_log, "jdata:" . print_r($jdata, 1));
    
        try {
            $clientId = config('services.mqtt.client_id', 'laravel_mqtt_client');
            $clientId .= "-pub-" . uniqid(); // パブリッシャー用にユニークなクライアントIDを生成

            $mqtt = new MqttClient($host, $port, $clientId);
    
            // ConnectionSettings オブジェクトを作成
            $settings = (new ConnectionSettings())
                ->setConnectTimeout(3)  // 接続タイムアウトを3秒に設定
                ->setUseTls(true)
                ->setTlsCertificateAuthorityFile(storage_path(config('services.mqtt.cert_ca')))
                ->setTlsClientCertificateFile(storage_path(config('services.mqtt.cert_crt')))
                ->setTlsClientCertificateKeyFile(storage_path(config('services.mqtt.cert_key')));
    
            // 接続設定を渡して接続
            $mqtt->connect($settings);
    
            // 接続が成功したか確認
            if (!$mqtt->isConnected()) {
                throw new \Exception("MQTT接続に失敗しました。");
            }
            
            $QoS_level = 0; // 0: 1回だけ送信(未接続時送信無し)
            $retain_flag = false; // true：受信側未接続状態で保持させる
            
            // メッセージ送信
            $mqtt->publish($topic, $json_message, $QoS_level, $retain_flag);
    
            // サーバー切断
            $mqtt->disconnect();

            make_error_log($error_log, "success");
            return ['success' => true, 'msg' => "MQTTメッセージを送信しました。"];

        } catch (\PhpMqtt\Client\Exceptions\MqttClientException $e) {
            $msg = "MQTT Client Error: " . $e->getMessage();
            make_error_log($error_log, "failure: " . $msg);
            return ['success' => false, 'msg' => $msg];
        } catch (\Exception $e) {
            $msg = "Error: " . $e->getMessage();
            make_error_log($error_log, "failure: " . $msg);
            return ['success' => false, 'msg' => $msg];
        }
    }
}
