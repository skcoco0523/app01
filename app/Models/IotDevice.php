<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

use App\Models\IotDeviceSignal;

class IotDevice extends Model
{
    use HasFactory;
    protected $fillable = ['hub_id', 'mac_addr', 'name', 'type', 'ver', 'pincode'];     //一括代入の許可

    //IoTデバイス一覧取得
    public static function getIotDeviceList($disp_cnt=null,$pageing=false,$page=1,$keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {
            $sql_cmd = DB::table('iot_devices as dev');
            $sql_cmd = $sql_cmd->leftJoin('users', 'dev.admin_user_id', '=', 'users.id');
            $sql_cmd = $sql_cmd->select('dev.*', 'users.id as uid', 'users.name as uname', 'dev.name as name');
            if($keyword){
                //管理者による検索
                if(get_proc_data($keyword,"admin_flag")){

                    if (isset($keyword['search_addr'])) 
                        $sql_cmd = $sql_cmd->where('dev.mac_addr', 'like', '%'. $keyword['search_addr']. '%');

                    if (isset($keyword['mac_addr'])) 
                        $sql_cmd = $sql_cmd->where('dev.mac_addr', $keyword['mac_addr']);

                    if (isset($keyword['search_owner_id'])) 
                        $sql_cmd = $sql_cmd->where('dev.admin_user_id',$keyword['search_owner_id']);

                    if (isset($keyword['search_type'])) 
                        $sql_cmd = $sql_cmd->where('dev.type',$keyword['search_type']);

                    if (isset($keyword['search_ver'])) 
                        $sql_cmd = $sql_cmd->where('dev.ver',$keyword['search_ver']);

                    if (isset($keyword['search_pincode'])) 
                        $sql_cmd = $sql_cmd->where('dev.pincode',$keyword['search_pincode']);

                //ユーザーによる検索
                }else{      
                    //本登録時の仮登録デバイス検索
                    if(get_proc_data($keyword,"final_register_flag")){  
                        $sql_cmd = $sql_cmd->where('dev.pincode',$keyword['pincode']);
                        $sql_cmd = $sql_cmd->where('dev.name',$keyword['name']);
                        $sql_cmd = $sql_cmd->whereNull('dev.admin_user_id');
                    }

                    if (isset($keyword['search_admin_uid']))
                        $sql_cmd = $sql_cmd->where('dev.admin_user_id',$keyword['search_admin_uid']);
                    
                    if (isset($keyword['search_id'])) 
                        $sql_cmd = $sql_cmd->where('dev.id',$keyword['search_id']);

                    if (isset($keyword['search_type'])) 
                        $sql_cmd = $sql_cmd->where('dev.type',$keyword['search_type']);
                }
                //並び順
                if(get_proc_data($keyword,"type_asc"))     $sql_cmd = $sql_cmd->orderBy('dev.type',             'asc');
                if(get_proc_data($keyword,"cdate_asc"))     $sql_cmd = $sql_cmd->orderBy('dev.created_at',      'asc');
                if(get_proc_data($keyword,"udate_asc"))     $sql_cmd = $sql_cmd->orderBy('dev.updated_at',      'asc');
                
                if(get_proc_data($keyword,"type_desc"))     $sql_cmd = $sql_cmd->orderBy('dev.type',            'desc');
                if(get_proc_data($keyword,"cdate_desc"))    $sql_cmd = $sql_cmd->orderBy('dev.created_at',      'desc');
                if(get_proc_data($keyword,"udate_desc"))    $sql_cmd = $sql_cmd->orderBy('dev.updated_at',      'desc');
            }
    
            //$sql_cmd                = $sql_cmd->orderBy('created_at', 'desc');
    
            // ページング・取得件数指定・全件で分岐
            if ($pageing){
                if ($disp_cnt === null) $disp_cnt=5;
                $sql_cmd = $sql_cmd->paginate($disp_cnt, ['*'], 'page', $page);
            }                       
            elseif($disp_cnt !== null)          $sql_cmd = $sql_cmd->limit($disp_cnt)->get();
            else                                $sql_cmd = $sql_cmd->get();
    
            $iotdevice_list = $sql_cmd;

            $device_info = config('common.device_info');
            //dd($iotdevice_list);
            foreach($iotdevice_list as $key => $iotdevice){
                // 関連デバイスは詳細検索時のみ取得
                if (isset($keyword['search_detail']) && $keyword['search_detail'] === true) {
                    //デバイスの信号
                    $iotdevice->signal_list = IotDeviceSignal::where('device_id', $iotdevice->id)->get();
                    // 親デバイス（Hub）
                    if ($iotdevice->hub_id>0) {
                        $iotdevice->parent_device = IotDevice::where('id', $iotdevice->hub_id)->first();
                    } else {
                        $iotdevice->parent_device = null;
                    }
                    // 子デバイス
                    $iotdevice->child_devices = IotDevice::where('hub_id', $iotdevice->id)->get();  // 子デバイス一覧
                    foreach($iotdevice->child_devices as $key => $child){
                        $child->type_name   = $device_info[$child->type]['type_name'] ?? null;
                        $child->icon_class  = $device_info[$child->type]['icon_class'] ?? null;
                        $child->desc        = $device_info[$child->type]['description'] ?? null;
                    }
                }
                
                $iotdevice->type_name   = $device_info[$iotdevice->type]['type_name'] ?? null;
                $iotdevice->icon_class  = $device_info[$iotdevice->type]['icon_class'] ?? null;
                $iotdevice->desc        = $device_info[$iotdevice->type]['description'] ?? null;
            }

            //dd($iotdevice_list);

            return $iotdevice_list; 
            
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            //ループ処理でエラーになるため、空の配列を返す
            return [];
        }
    }

    //IoTデバイス登録
    public static function createIotDevice($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {

            $error_code = 0;
            if(!isset($data['mac_addr']))       $error_code = 1;   //データ不足
            if(!isset($data['type']))           $error_code = 2;   //データ不足
            if(!isset($data['ver']))            $error_code = 3;   //データ不足
            if(!isset($data['pincode']))        $error_code = 4;   //データ不足
            
            
            $keyword = array('admin_flag'=>true,'mac_addr'=>$data['mac_addr']);
            $iotdevice_list = IotDevice::getIotDeviceList(1,false,null,$keyword);  //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ
            //dd($iotdevice_list);
            if($error_code){
                make_error_log($error_log,"error_code=".$error_code);
                return ['id' => null, 'error_code' => $error_code];
            }
            //mac_addrのユニーク規制
            if(count($iotdevice_list)!=0) {
                $error_code = -2;
                return ['id' => null, 'error_code' => $error_code];
            }

            //dd($data);
            $request = self::create($data);
            $request_id = $request->id;

            make_error_log($error_log,"success");
            
            return ['id' => $request_id, 'error_code' => $error_code];   //追加成功

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //追加失敗
        }
        
    }
    //IoTデバイス変更
    public static function chgIotDevice($data) 
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log,"-------start-------");

            $device = IotDevice::where('id', $data['id'])->first();
            
            if(!$device){
                make_error_log($error_log,".not applicable:".$data['mac_addr']);
                return ['id' => null, 'error_code' => -1];   //更新失敗
            }
            //dd($data);
            
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
            
            if (isset($data['name']) && $device->name != $data['name'])
                $updateData['name'] = $data['name']; 

            //NULL更新を許容
            if (array_key_exists('pincode', $data) && $device->pincode != $data['pincode'])
                $updateData['pincode'] = $data['pincode']; 


            make_error_log($error_log,"chg_data=".print_r($updateData,1));
            if(count($updateData) > 0){
                IotDevice::where('id', $device->id)->update($updateData);
                make_error_log($error_log,"success");
            }
            
            return ['id' => $device->id, 'error_code' => 0];   //更新成功

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['error_code' => -1];   //更新失敗
        }
    }
    //IoTデバイス削除
    public static function delIotDevice($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log,"delete_id=".$data['id']);
            $user_id = Auth::id();

            //if(get_proc_data($data,"admin_flag")){    //管理画面での削除
                //他データはリレーションでカスケード削除
                IotDevice::where('id', $data['id'])->delete();
                make_error_log($error_log,"admin_id=".$user_id);
            
            //}else{//ユーザーによるデバイス削除  
            //}
            make_error_log($error_log,"success");
            return ['id' => null, 'error_code' => 0];   //削除成功

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //削除失敗

        }
    }

}

