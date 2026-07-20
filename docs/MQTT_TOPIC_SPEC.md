# MQTT通信仕様書 (MQTT_TOPIC_SPEC.md)

## 1. 概要
本システムは、AWS IoT CoreをMQTTブローカーとして、Laravelバックエンドサーバー（`MqttListener.php`）とスマートリモコンデバイス（ESP32: `MQTT.cpp`）の間で双方向通信を行います。
認証には、初回プロビジョニング（クレーム証明書）および通常運用（個別配布の本番用証明書）の2段階方式を採用しています。

---

## 2. トピック一覧

### ① 通常運用トピック
| 方向 | トピック名 | 用途 | 補足 |
| :--- | :--- | :--- | :--- |
| **ESP32 → Laravel** | `device/{mac_addr}/` | デバイスの状態通知、IR信号受信通知など | 末尾に `/` が含まれます。Laravel側は `device/#` で一括購読。 |
| **Laravel → ESP32** | `web/{mac_addr}` | サーバーからの登録応答、遠隔操作、設定変更など | ESP32が個別起動時に自身のアドレスを購読。 |

### ② AWS IoT Core フリートプロビジョニング用（初回接続時のみ）
| 方向 | トピック名 | 用途 |
| :--- | :--- | :--- |
| **ESP32 ⇄ AWS** | `$aws/certificates/create/json/#` | 新規本番証明書・秘密鍵の要求と取得 |
| **ESP32 → AWS** | `$aws/provisioning-templates/SK-HOME-FleetTemplate/provision/json` | テンプレートの実行依頼（アクティベート） |

---

## 3. メッセージ共通ペイロードフォーマット (JSON)

すべてのメッセージ（通常運用）は、以下の共通キーを持つJSONオブジェクト、またはシリアライズされたJSON文字列として送受信されます。

```json
{
  "mac_addr": "XX:XX:XX:XX:XX:XX",
  "device_name": "Living-Remote",
  "ver": "1.0.0",
  "command": "コマンド名",
  "data": "追加データ（JSON文字列、またはオブジェクト）"
}