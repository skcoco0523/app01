<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;


class VirtualRemoteBlade extends Model
{
    use HasFactory;
    protected $fillable = ['kind', 'company_name', 'product_name', 'blade_name', 'test_flag'];     //一括代入の許可

    //リモコンデザイン取得
    public static function getVirtualRemoteBladeList($disp_cnt=null,$pageing=false,$page=1,$keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $sql_cmd = DB::table('virtual_remote_blades as blade');
            $sql_cmd = $sql_cmd->select('blade.*');
            if($keyword){
    
                //管理者による検索
                if(get_proc_data($keyword,"admin_flag")){
                    
                    if (isset($keyword['search_id'])) 
                        $sql_cmd = $sql_cmd->where('blade.id',$keyword['search_id']);

                    if (isset($keyword['search_kind'])) 
                        $sql_cmd = $sql_cmd->where('blade.kind',$keyword['search_kind']);

                    if (isset($keyword['search_name'])) 
                        $sql_cmd = $sql_cmd->where('blade.blade_name', 'like',  '%'. $keyword['search_name']. '%');

                    if (isset($keyword['search_test_flag'])) 
                        $sql_cmd = $sql_cmd->where('blade.test_flag',$keyword['search_test_flag']);

                //ユーザーによる検索
                }else{
                    $sql_cmd = $sql_cmd->where('blade.test_flag',0);
                    
                    if (isset($keyword['search_kind'])) 
                        $sql_cmd = $sql_cmd->where('blade.kind',$keyword['search_kind']);

                }
                //並び順
                //if(get_proc_data($keyword,"name_asc"))      $sql_cmd = $sql_cmd->orderBy('blade.device_name',     'asc');
                if(get_proc_data($keyword,"cdate_asc"))     $sql_cmd = $sql_cmd->orderBy('blade.created_at',      'asc');
                if(get_proc_data($keyword,"udate_asc"))     $sql_cmd = $sql_cmd->orderBy('blade.updated_at',      'asc');
                
                if(get_proc_data($keyword,"cdate_desc"))    $sql_cmd = $sql_cmd->orderBy('blade.created_at',      'desc');
                if(get_proc_data($keyword,"udate_desc"))    $sql_cmd = $sql_cmd->orderBy('blade.updated_at',      'desc');
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

    //リモコンデザイン登録
    public static function createVirtualRemoteBlade($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {

            $error_code = 0;
            if(!isset($data['remote_kind']))    $error_code = 1;   //データ不足
            if(!isset($data['blade_name']))     $error_code = 2;   //データ不足
            
            if($error_code){
                make_error_log($error_log,"error_code=".$error_code);
                return ['id' => null, 'error_code' => $error_code];
            }
            
            $data['kind'] = $data['remote_kind'];
            $request = self::create($data);
            $request_id = $request->id;
            make_error_log($error_log,"success");
            return ['id' => $request_id, 'error_code' => $error_code];   //追加成功

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //追加失敗
        }
        
    }
    //リモコンデザイン変更
    public static function chgVirtualRemoteBlade($data) 
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log,"-------start-------");

            //登録者チェック
            $user_id = Auth::id();
            make_error_log($error_log,"user_id:".$user_id);

            $remote = VirtualRemoteBlade::where('id', $data['id'])->first();
            if($remote){
                //dd($data,$remote);
                
                // 更新対象となるカラムと値を連想配列に追加
                $updateData = [];
                if (isset($data['kind']) && $remote->kind != $data['kind'])
                    $updateData['kind'] = $data['kind']; 
                
                if (isset($data['blade_name']) && $remote->blade_name != $data['blade_name'])
                    $updateData['blade_name'] = $data['blade_name']; 
                
                if (isset($data['test_flag']) && $remote->test_flag != $data['test_flag'])
                    $updateData['test_flag'] = $data['test_flag']; 

                make_error_log($error_log,"chg_data=".print_r($updateData,1));
                if(count($updateData) > 0){
                    VirtualRemoteBlade::where('id', $data['id'])->update($updateData);
                    make_error_log($error_log,"success");
                }
                
                return ['id' => $remote->id, 'error_code' => 0];   //更新成功

            } else {
                make_error_log($error_log,".not applicable:".$data['mac_addr']);
                return ['id' => null, 'error_code' => -1];   //更新失敗
            }

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['error_code' => -1];   //更新失敗
        }
    }
    //リモコンデザイン削除
    public static function delVirtualRemoteBlade($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            make_error_log($error_log,"delete_id=".$data['id']);
            $user_id = Auth::id();

            if($data['admin_flag']){    //管理画面での削除
                //他データはリレーションでカスケード削除
                VirtualRemoteBlade::where('id', $data['id'])->delete();
                make_error_log($error_log,"admin_id=".$user_id);
            }

            make_error_log($error_log,"success");
            return ['id' => null, 'error_code' => 0];   //削除成功

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //削除失敗

        }
    }
}

