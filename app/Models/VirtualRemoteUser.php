<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;


class VirtualRemoteUser extends Model
{
    use HasFactory;
    protected $fillable = ['remote_id', 'user_id', 'admin_flag', 'stop_flag'];     //一括代入の許可
    //protected $table = 'virtual_remote_users';

    //ユーザー別仮想リモコン一覧取得

    //仮想リモコン一覧取得
    public static function getVirtualRemoteUserList($disp_cnt=null,$pageing=false,$page=1,$keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {
            $sql_cmd = DB::table('virtual_remote_users as remote_u');
            $sql_cmd = $sql_cmd->leftJoin('virtual_remotes as remote', 'remote_u.remote_id', '=', 'remote.id');
            $sql_cmd = $sql_cmd->leftJoin('virtual_remote_blades as remote_b', 'remote.blade_id', '=', 'remote_b.id');
            $sql_cmd = $sql_cmd->leftJoin('users', 'remote_u.user_id', '=', 'users.id');
            $sql_cmd = $sql_cmd->select('remote_u.*', 'remote.remote_name as name', 'remote.admin_user_id', 'remote_b.kind', 'remote_b.blade_name', 'users.name as uname');
            if($keyword){
    
                //管理者による検索
                if(get_proc_data($keyword,"admin_flag")){

                    if (isset($keyword['search_admin_flag'])) 
                        $sql_cmd = $sql_cmd->where('remote_u.admin_flag',$keyword['search_admin_flag']);

                    if (isset($keyword['search_stop_flag'])) 
                        $sql_cmd = $sql_cmd->where('remote_u.stop_flag',$keyword['search_stop_flag']);

                //ユーザーによる検索
                }else{
                    $sql_cmd = $sql_cmd->where('remote_u.user_id', Auth::id());

                    //リモコン詳細情報
                    if (isset($keyword['search_remote_id'])) 
                        $sql_cmd = $sql_cmd->where('remote_u.id',$keyword['search_remote_id']);


                }
                //並び順
                //if(get_proc_data($keyword,"name_asc"))      $sql_cmd = $sql_cmd->orderBy('remote_u.device_name',     'asc');
                if(get_proc_data($keyword,"cdate_asc"))     $sql_cmd = $sql_cmd->orderBy('remote_u.created_at',      'asc');
                if(get_proc_data($keyword,"udate_asc"))     $sql_cmd = $sql_cmd->orderBy('remote_u.updated_at',      'asc');
                
                if(get_proc_data($keyword,"cdate_desc"))    $sql_cmd = $sql_cmd->orderBy('remote_u.created_at',      'desc');
                if(get_proc_data($keyword,"udate_desc"))    $sql_cmd = $sql_cmd->orderBy('remote_u.updated_at',      'desc');
            }
    
            //$sql_cmd                = $sql_cmd->orderBy('created_at', 'desc');
    
            // ページング・取得件数指定・全件で分岐
            if ($pageing){
                if ($disp_cnt === null) $disp_cnt=5;
                $sql_cmd = $sql_cmd->paginate($disp_cnt, ['*'], 'page', $page);
            }                       
            elseif($disp_cnt !== null)          $sql_cmd = $sql_cmd->limit($disp_cnt)->get();
            else                                $sql_cmd = $sql_cmd->get();
    
            $virtual_remote_list = $sql_cmd;

            //テーブル用アイコン定義
            foreach($virtual_remote_list as $remote) {
                $remote->icon_class = config('common.remote_kind_icons')[$remote->kind] ?? null;
            }

            //dd($virtual_remote_list);

            return $virtual_remote_list; 
            
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            //ループ処理でエラーになるため、空の配列を返す
            return [];
        }
    }

    //ユーザー別仮想リモコン登録
    public static function createVirtualRemoteUser($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {

            $error_code = 0;
            if(!isset($data['remote_id']))      $error_code = 1;   //データ不足
            if(!isset($data['user_id']))        $error_code = 2;   //データ不足
            
            if($error_code){
                make_error_log($error_log,"error_code=".$error_code);
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
    
    //ユーザー別仮想リモコン削除
    public static function delVirtualRemoteUser($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            //他データはリレーションでカスケード削除
            make_error_log($error_log,"delete_id=".$data['id']);
            self::where('id', $data['id'])->delete();

            make_error_log($error_log,"success");
            return ['id' => null, 'error_code' => 0];   //削除成功

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //削除失敗

        }
    }

    /*
    //ユーザー別仮想リモコン変更
    public static function chgVirtualRemoteUser($data) 
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log,"-------start-------");

            //登録者チェック
            $user_id = Auth::id();
            make_error_log($error_log,"user_id:".$user_id);
            
            $device = IotDeviceUser::where('mac_addr', $data['mac_addr'])->first();
            if($device){
                if($user_id == $device->admin_user_id){
                    make_error_log($error_log,"error_code:1");
                    return ['id' => null, 'error_code' => 1];   //自身で使用済み
                }elseif($device->admin_user_id != NULL){
                    make_error_log($error_log,"error_code:2");
                    return ['id' => null, 'error_code' => 2];   //他ユーザーにて使用済み
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
                    IotDeviceUser::where('mac_addr', $data['mac_addr'])->update($updateData);
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

    */

}

