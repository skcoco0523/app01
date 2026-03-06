<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

class NoteShare extends Model
{
    use HasFactory;
    protected $fillable = ['note_id', 'user_id', 'admin_flag'];     //一括代入の許可
    //protected $table = 'note_shares';

    //共有されているメモ一覧取得
    public static function getSharedNoteList($disp_cnt=null,$pageing=false,$page=1,$keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {
            $sql_cmd = DB::table('note_shares as note_share');
            $sql_cmd = $sql_cmd->leftJoin('notes as note', 'note_share.note_id', '=', 'note.id');
            $sql_cmd = $sql_cmd->leftJoin('users as user', 'note.user_id', '=', 'user.id');         //所有者情報取得
            $sql_cmd = $sql_cmd->select('note.*', 'note.id as note_id', 'note_share.admin_flag', 'note_share.id as note_share_id', 'note_share.user_id as user_id', 'user.id as owner_user_id', 'user.name as owner_name');

            if($keyword){
    
                //管理者による検索
                if(get_proc_data($keyword,"admin_flag")){

                    if (isset($keyword['search_admin_flag'])) 
                        $sql_cmd = $sql_cmd->where('note_share.admin_flag',$keyword['search_admin_flag']);

                //ユーザーによる検索
                }else{
                    $sql_cmd = $sql_cmd->where('note_share.user_id', Auth::id());

                    //リモコン詳細情報
                    if (isset($keyword['search_note_id'])) 
                        $sql_cmd = $sql_cmd->where('note_share.note_id',$keyword['search_note_id']);


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
    
            $note_list = $sql_cmd;
            $note_info = config('common.note_colors');
            foreach($note_list as $note) {
                $note->owner_flag   = false;
                //メモ背景色定義
                $note->color_name   = $note_info[$note->color_num]['name'] ?? null;
                $note->color_code   = $note_info[$note->color_num]['code'] ?? null;

            }

            //dd($note_list);

            return $note_list; 
            
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            //ループ処理でエラーになるため、空の配列を返す
            return [];
        }
    }

    //共有しているメモ一覧取得
    public static function getSharingNoteList($disp_cnt=null,$pageing=false,$page=1,$keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {
            $sql_cmd = DB::table('note_shares as note_share');
            $sql_cmd = $sql_cmd->leftJoin('notes as note', 'note_share.note_id', '=', 'note.id');
            $sql_cmd = $sql_cmd->leftJoin('users as user', 'note_share.user_id', '=', 'user.id');         //共有者情報取得
            $sql_cmd = $sql_cmd->select('note.*', 'note.id as note_id', 'note_share.admin_flag', 'note_share.id as note_share_id', 'note.user_id as user_id', 'user.id as share_user_id', 'user.name as share_user_name');

            if($keyword){
    
                //管理者による検索
                if(get_proc_data($keyword,"admin_flag")){

                    if (isset($keyword['search_admin_flag'])) 
                        $sql_cmd = $sql_cmd->where('note_share.admin_flag',$keyword['search_admin_flag']);

                //ユーザーによる検索
                }else{
                    $sql_cmd = $sql_cmd->where('note.user_id', Auth::id());

                    //詳細情報
                    if (isset($keyword['search_note_id'])) 
                        $sql_cmd = $sql_cmd->where('note.id',$keyword['search_note_id']);

                    if (isset($keyword['search_friend_id'])) 
                        $sql_cmd = $sql_cmd->where('note_share.user_id',$keyword['search_friend_id']);

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
    
            $note_list = $sql_cmd;
            $note_info = config('common.note_colors');
            foreach($note_list as $note) {
                $note->owner_flag   = true;
                //メモ背景色定義
                $note->color_name   = $note_info[$note->color_num]['name'] ?? null;
                $note->color_code   = $note_info[$note->color_num]['code'] ?? null;

            }

            //dd($note_list);

            return $note_list; 
            
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            //ループ処理でエラーになるため、空の配列を返す
            return [];
        }
    }
    
    //共有メモ登録
    public static function createShareNote($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {
            $error_code = 0;
            if(!isset($data['user_id']))    $error_code = 1;   //データ不足
            if(!isset($data['note_id']))    $error_code = 2;   //データ不足
            
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

    //共有メモ削除
    public static function delShareNote($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            //他データはリレーションでカスケード削除
            make_error_log($error_log,"delete_id=".$data['id']);
            $note = NoteShare::where('id', $data['id'])->first();
            self::where('id', $data['id'])->delete();

            make_error_log($error_log,"success");
            return ['id' => null, 'error_code' => 0];   //削除成功

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['id' => null, 'error_code' => -1];   //削除失敗
        }
    }

    //共有メモ変更
    public static function chgShareNote($data) 
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {

            $note = NoteShare::where('id', $data['id'])->first();
            if(!$note){
                make_error_log($error_log,".not found id:".$data['id']);
                return ['id' => null, 'error_code' => -1];   //更新失敗
            }
            // 更新対象となるカラムと値を連想配列に追加
            $updateData = [];   
            if(isset($data['admin_flag']))  $updateData['admin_flag']   = $data['admin_flag']; 

            make_error_log($error_log,"after_data=".print_r($data,1));
            self::where('id', $data['id'])->update($updateData);
            
            make_error_log($error_log,"success");
            return ['error_code' => 0];   //更新成功

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['error_code' => -1];   //更新失敗
        }
    }

}

