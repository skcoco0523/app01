<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class CommonConfig extends Model
{
    use HasFactory;

    protected $fillable = ['config_name', 'type', 'value1', 'value2', 'description'];

    // デフォルト値の設定
    private static function setDefaultValue($name)
    {
        // 広告関係設定
        if($name == 'adv_score_select')             self::upsertValue('adv_score_select',       'int', 1, 0, '広告スコアリング用の設定値(選択時)');
        if($name == 'adv_score_detail_view')        self::upsertValue('adv_score_detail_view',  'int', 3, 0, '広告スコアリング用の設定値(詳細表示時)');
        if($name == 'adv_score_dislike')            self::upsertValue('adv_score_dislike',      'int', -1, 0, '広告スコアリング用の設定値(不同意時)');
        if($name == 'adv_score_bonus')              self::upsertValue('adv_score_bonus',        'int', 3, 0, '広告スコアリング用の設定値(連続選択ボーナス)');
        if($name == 'adv_show_enable')              self::upsertValue('adv_show_enable',        'bool', 1, 0, '広告表示有効化設定');
        if($name == 'adv_popup_interval')           self::upsertValue('adv_popup_interval',     'int', 180, 0, '広告表示間隔(秒)');

        // ポイントパック設定
        if($name == 'po_pack1')                     self::upsertValue('po_pack1',                 'int', 100, 130, 'お試し');
        if($name == 'po_pack2')                     self::upsertValue('po_pack2',                 'int', 300, 450, 'ライト');
        if($name == 'po_pack3')                     self::upsertValue('po_pack3',                 'int', 500, 750, '標準');
        if($name == 'po_pack4')                     self::upsertValue('po_pack4',                 'int', 1000, 1500, 'お得');
        if($name == 'po_pack5')                     self::upsertValue('po_pack5',                 'int', 2000, 3500, '大容量');
        if($name == 'po_pack6')                     self::upsertValue('po_pack6',                 'int', 3000, 6000, '特大');
        if($name == 'po_pack_late')                 self::upsertValue('po_pack_late',             'int', 10, 0, '購入額に対してのpt付与レート');

        // 無料ポイント設定
        if($name == 'po_free')                      self::upsertValue('po_free',                  'int', 90, 0, '毎月付与される無料pt');
        if($name == 'po_free_flag')                 self::upsertValue('po_free_flag',             'bool', 1, 0, '毎月無料pt付与フラグ');
        if($name == 'po_ad_reward')                 self::upsertValue('po_ad_reward',             'int', 10, 0, '広告視聴によるpt付与数');

        // 利用ポイント設定
        if ($name == 'po_whisper')                  self::upsertValue('po_whisper',               'int', 1, 50, 'STT（文字起こし）の消費ptと無料枠');
        if ($name == 'po_ai_text')                  self::upsertValue('po_ai_text',               'int', 1,  0, 'AI応答（テキスト表示）の消費ptと無料枠');
        if ($name == 'po_ai_voice')                 self::upsertValue('po_ai_voice',              'int', 2,  0, 'AI応答（音声会話）の消費ptと無料枠');

        // その他設定
        if ($name == 'stop_admin_connect_notify')   self::upsertValue('stop_admin_connect_notify',  'bool', 0, 0, '管理者デバイス接続通知停止');
        if ($name == 'stop_admin_api_token')        self::upsertValue('stop_admin_api_token',       'bool', 0, 0, '管理者デバイスへトークン送信停止');
        if ($name == 'maint_mode_flag')             self::upsertValue('maint_mode_flag',          'bool', 0, 0, 'メンテナンスモード');

        

    }

    //設定名から値を取得する
    public static function getValues(array $names)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        // キャッシュから一括取得を試みる
        $cached_configs = Cache::get('common_config_data');
        if (!$cached_configs) {
            // キャッシュがなければ全件取得してキャッシュ
            $cached_configs = self::all()->keyBy('config_name');
            Cache::forever('common_config_data', $cached_configs);
        }

        $result = [];

        foreach ($names as $name) {
            // キャッシュ内に存在するかチェック
            if (!isset($cached_configs[$name])) {
                // 未登録ならデフォルト作成（upsertValue内でキャッシュがクリアされる）
                self::setDefaultValue($name);
                // 再帰的に自分を呼んで最新のキャッシュを反映させるか、DBから直接取る
                $config = self::where('config_name', $name)->first();
                if (!$config) {
                    make_error_log($error_log, "config create failed name={$name}");
                    continue;
                }
                // 再度全件キャッシュを更新しておく（頻繁に発生しない想定）
                $cached_configs = self::all()->keyBy('config_name');
                Cache::forever('common_config_data', $cached_configs);
            } else {
                $config = $cached_configs[$name];
            }

            $value1 = $config->value1;
            $value2 = $config->value2;
            if ($config->type == 'int' || $config->type == 'range') {
                $value1 = (int)$value1;
                $value2 = (int)$value2;
            } else if ($config->type == 'string') {
                $value1 = (string)$value1;
                $value2 = (string)$value2;
            } else {
                make_error_log($error_log, "unknown type=" . $config->type);
            }

            $result[$config->config_name] = (object)[
                'id' => $config->id,
                'config_name' => $config->config_name,
                'type' => $config->type,
                'value1' => $value1,
                'value2' => $value2,
                'description' => $config->description,
                'created_at' => $config->created_at,
                'updated_at' => $config->updated_at,
            ];

        }
        return $result;
    }

    // 設定を保存・更新する(アップサート)
    public static function upsertValue($name, $type, $value1, $value2, $description = null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        make_error_log($error_log, "upsertValue called with name={$name}, type={$type}, value1={$value1}, value2={$value2}, description={$description}");
        $data = [];
        if ($type) $data['type'] = $type;
        if ($value1 !== null) $data['value1'] = (string)$value1;
        if ($value2 !== null) $data['value2'] = (string)$value2;
        if ($description) $data['description'] = $description;

        $config = self::updateOrCreate(['config_name' => $name], $data);
        
        // バージョンをインクリメント（フロント側の検知用）
        Cache::increment('common_config_version');

        // サーバー側にある設定データのキャッシュを削除（クリア）する
        Cache::forget('common_config_data');

        return $config;
    }
}
