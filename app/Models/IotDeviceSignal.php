<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class IotDeviceSignal extends Model
{
    use HasFactory;

    // テーブル名は規約通りなので省略可
    // protected $table = 'iot_device_signals';

    // クリエイティブな一括代入を許可する属性
    protected $fillable = [
        'device_id',
        'remote_id',
        'button_num',
        'category_name',
        'signal_name',
        'signal_data'
    ];

    // timestampsはマイグレーションで定義されていないため無効化
    public $timestamps = false;

    /**
     * IoTデバイス信号登録
     * 
     * @param array $data
     * @return array ['success' => bool, 'msg' => string, 'id' => int|null]
     */
    public static function createIotDeviceSignal($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "------- start -------");

        try {
            // 必須チェック
            $required = ['device_id', 'remote_id', 'button_num', 'category_name', 'signal_name', 'signal_data'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    $msg = "Missing required field: {$field}";
                    make_error_log($error_log, "error: " . $msg);
                    return ['success' => false, 'msg' => $msg];
                }
            }

            // 重複チェック (unique制約: device_id, remote_id, button_num)
            $exists = self::where('device_id', $data['device_id'])
                ->where('remote_id', $data['remote_id'])
                ->where('button_num', $data['button_num'])
                ->first();

            if ($exists) {
                // 更新するか、エラーにするか。ここでは更新（上書き）とする
                $exists->update([
                    'category_name' => $data['category_name'],
                    'signal_name'   => $data['signal_name'],
                    'signal_data'   => $data['signal_data'],
                ]);
                make_error_log($error_log, "updated existing signal. id: " . $exists->id);
                return [
                    'success' => true,
                    'msg'     => "信号データを更新しました。",
                    'id'      => $exists->id
                ];
            }

            // 新規作成
            $new_signal = self::create($data);
            make_error_log($error_log, "created new signal. id: " . $new_signal->id);

            return [
                'success' => true,
                'msg'     => "信号データを登録しました。",
                'id'      => $new_signal->id
            ];

        } catch (\Exception $e) {
            $msg = "信号登録中にエラーが発生しました: " . $e->getMessage();
            make_error_log($error_log, $msg);
            return ['success' => false, 'msg' => $msg];
        }
    }

    /**
     * IoTデバイス信号削除
     * 
     * @param int $id
     * @return array
     */
    public static function delIotDeviceSignal($id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "------- start -------");

        try {
            $signal = self::find($id);
            if (!$signal) {
                return ['success' => false, 'msg' => "対象の信号が見つかりません。"];
            }

            $signal->delete();
            make_error_log($error_log, "success. deleted id: " . $id);
            return ['success' => true, 'msg' => "信号を削除しました。"];

        } catch (\Exception $e) {
            $msg = "信号削除中にエラーが発生しました: " . $e->getMessage();
            make_error_log($error_log, $msg);
            return ['success' => false, 'msg' => $msg];
        }
    }
}
