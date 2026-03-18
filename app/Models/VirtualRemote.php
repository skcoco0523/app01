<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;


class VirtualRemote extends Model
{
    use HasFactory;
    protected $fillable = ['kHz', 'admin_user_id', 'remote_name', 'blade_id'];     //一括代入の許可

    //仮想リモコン一覧取得
    public static function getVirtualRemoteList($disp_cnt=null,$pageing=false,$page=1,$keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = DB::table('virtual_remotes as remote');
            $sql_cmd = $sql_cmd->leftJoin('users', 'remote.admin_user_id', '=', 'users.id');
            $sql_cmd = $sql_cmd->select('remote.*', 'users.id as uid', 'users.name as uname', 'remote.remote_name as name');
            if($keyword){
    
                //管理者による検索
                if(get_proc_data($keyword,"admin_flag")){

                    if (isset($keyword['search_kHz'])) 
                        $sql_cmd = $sql_cmd->where('remote.kHz',$keyword['search_kHz']);

                    if (isset($keyword['search_owner_id'])) 
                        $sql_cmd = $sql_cmd->where('remote.admin_user_id',$keyword['search_owner_id']);

                    if (isset($keyword['search_blade_name'])) 
                        $sql_cmd = $sql_cmd->where('remote.blade_name',$keyword['search_blade_name']);

                //ユーザーによる検索
                }else{
                    $sql_cmd = $sql_cmd->where('remote.admin_user_id', Auth::id());
                    
                    if (isset($keyword['search_id'])) 
                        $sql_cmd = $sql_cmd->where('remote.id',$keyword['search_id']);

                }
                //並び順
                //if(get_proc_data($keyword,"name_asc"))      $sql_cmd = $sql_cmd->orderBy('remote.device_name',     'asc');
                if(get_proc_data($keyword,"cdate_asc"))     $sql_cmd = $sql_cmd->orderBy('remote.created_at',      'asc');
                if(get_proc_data($keyword,"udate_asc"))     $sql_cmd = $sql_cmd->orderBy('remote.updated_at',      'asc');
                
                if(get_proc_data($keyword,"cdate_desc"))    $sql_cmd = $sql_cmd->orderBy('remote.created_at',      'desc');
                if(get_proc_data($keyword,"udate_desc"))    $sql_cmd = $sql_cmd->orderBy('remote.updated_at',      'desc');
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

            //dd($virtual_remote_list);

            return $virtual_remote_list; 
            
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            //ループ処理でエラーになるため、空の配列を返す
            return [];
        }
    }

    //仮想リモコン登録
    public static function createVirtualRemote($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {

            $error_code = 0;
            if(!isset($data['blade_id']))           $error_code = 1;   //データ不足
            if(!isset($data['remote_name']))        $error_code = 2;   //データ不足
            if(!isset($data['admin_user_id']))      $error_code = 3;   //データ不足
            
            
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
    //仮想リモコン削除
    public static function delVirtualRemote($data)
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

    //仮想リモコン変更
    public static function chgVirtualRemote($data) 
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log,"-------start-------");

            //登録者チェック
            $user_id = Auth::id();
            make_error_log($error_log,"user_id:".$user_id);

            $remote = VirtualRemote::where('id', $data['id'])->first();
            if($remote){
                
                // 更新対象となるカラムと値を連想配列に追加
                $updateData = [];
                if (isset($data['kHz']) && $remote->kHz != $data['kHz'])
                    $updateData['kHz'] = $data['kHz']; 
                
                if (isset($data['admin_user_id']) && $remote->admin_user_id != $data['admin_user_id'])
                    $updateData['admin_user_id'] = $data['admin_user_id']; 
                
                if (isset($data['remote_name']) && $remote->remote_name != $data['remote_name'])
                    $updateData['remote_name'] = $data['remote_name']; 
                
                if (isset($data['blade_id']) && $remote->blade_id != $data['blade_id'])
                    $updateData['blade_id'] = $data['blade_id']; 
                

                make_error_log($error_log,"chg_data=".print_r($updateData,1));
                if(count($updateData) > 0){
                    VirtualRemote::where('id', $data['id'])->update($updateData);
                    make_error_log($error_log,"success");
                }
                
                return ['id' => $remote->id, 'error_code' => 0];   //更新成功

            } else {
                make_error_log($error_log,".not applicable:".$data['id']);
                return ['id' => null, 'error_code' => -1];   //更新失敗
            }

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['error_code' => -1];   //更新失敗
        }
    }

}

