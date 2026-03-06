<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

class IotDeviceSignal extends Model
{
    use HasFactory;
    protected $fillable = ['device_id', 'category_name', 'signal_name', 'signal_data'];     //一括代入の許可
    //protected $table = 'iot_device_signals';

    //IoTデバイス信号登録
    public static function createIotDeviceSignal($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log,"-------start-------");
            
            $device = IotDeviceSignal::where('device_id', $data['device_id'])->where('user_id', $data['user_id'])->first();
            if($device){
                make_error_log($error_log,"error_code:1");
                return ['id' => null, 'error_code' => 1];   //自身で使用済み
            }else{
                $error_code = 0;
                if(!isset($data['device_id']))      $error_code = 1;   //データ不足
                if(!isset($data['user_id']))        $error_code = 2;   //データ不足
                if(!isset($data['device_name']))    $error_code = 3;   //データ不足
                
                if($error_code){
                    make_error_log($error_log,"error_code=".$error_code);
                    return ['id' => null, 'error_code' => $error_code];
                }
                
                $request = self::create($data);
                $request_id = $request->id;
                make_error_log($error_log,"success");
                make_error_log($error_log,"device_id:".$data['device_id']."   user_id:".$data['user_id']);

                return ['id' => $request_id, 'error_code' => $error_code];   //追加成功
            }

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //追加失敗
        }
        
    }
    //IoTデバイス信号変更
    public static function chgIotDeviceSignal($data) 
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log,"-------start-------");

            //登録者チェック
            $user_id = Auth::id();
            make_error_log($error_log,"user_id:".$user_id);
            
            $device = IotDeviceSignal::where('mac_addr', $data['mac_addr'])->first();
            if($device){
                if($user_id == $device->admin_user_id){
                    make_error_log($error_log,"error_code:1");
                    return ['id' => null, 'error_code' => 1];   //自身で使用済み
                }elseif($device->admin_user_id != NULL){
                    make_error_log($error_log,"error_code:2");
                    return ['id' => null, 'error_code' => 2];   //他信号にて使用済み
                }
                
                // 更新対象となるカラムと値を連想配列に追加
                $updateData = [];
                if (isset($data['mac_addr']) && $device->mac_addr != $data['mac_addr'])
                    $updateData['mac_addr'] = $data['mac_addr']; 
                
                if (isset($data['ver']) && $device->ver != $data['ver'])
                    $updateData['ver'] = $data['ver']; 
                
                if (isset($data['type']) && $device->type != $data['type'])
                    $updateData['type'] = $data['type']; 
                
                if (isset($data['admin_user_id']) && $device->admin_user_id != $data['admin_user_id'])
                    $updateData['admin_user_id'] = $data['admin_user_id']; 

                make_error_log($error_log,"chg_data=".print_r($updateData,1));
                if(count($updateData) > 0){
                    IotDeviceSignal::where('mac_addr', $data['mac_addr'])->update($updateData);
                    make_error_log($error_log,"success");
                }
                
                return ['mac_addr' => $device->id, 'error_code' => 0];   //更新成功

            } else {
                return ['id' => null, 'error_code' => -1];   //更新失敗
            }

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['error_code' => -1];   //更新失敗
        }
    }

}

