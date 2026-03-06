<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Auth;

class Note extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'title', 'content', 'color_num', 'edit_lock_flag'];     //一括代入の許可
    //protected $table = 'notes';

    //myメモ一覧取得
    public static function getNoteList($disp_cnt=null,$pageing=false,$page=1,$keyword=null)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {
            $sql_cmd = DB::table('notes as note');
            if($keyword){
    
                //管理者による検索
                if(get_proc_data($keyword,"admin_flag")){

                    if (isset($keyword['search_admin_flag'])) 
                        $sql_cmd = $sql_cmd->where('note.admin_flag',$keyword['search_admin_flag']);

                //ユーザーによる検索
                }else{
                    $sql_cmd = $sql_cmd->where('note.user_id', Auth::id());

                    //メモ詳細情報
                    if (isset($keyword['search_note_id'])) 
                        $sql_cmd = $sql_cmd->where('note.id',$keyword['search_note_id']);

                    if (isset($keyword['search_color_num'])) 
                        $sql_cmd = $sql_cmd->where('note.color_num',$keyword['search_color_num']);
                }
                //並び順
                //if(get_proc_data($keyword,"name_asc"))      $sql_cmd = $sql_cmd->orderBy('note.device_name',     'asc');
                if(get_proc_data($keyword,"color_num_asc")) $sql_cmd = $sql_cmd->orderBy('note.color_num',      'asc');
                if(get_proc_data($keyword,"cdate_asc"))     $sql_cmd = $sql_cmd->orderBy('note.created_at',      'asc');
                if(get_proc_data($keyword,"udate_asc"))     $sql_cmd = $sql_cmd->orderBy('note.updated_at',      'asc');
                
                if(get_proc_data($keyword,"cdate_desc"))    $sql_cmd = $sql_cmd->orderBy('note.created_at',      'desc');
                if(get_proc_data($keyword,"udate_desc"))    $sql_cmd = $sql_cmd->orderBy('note.updated_at',      'desc');
            }
    
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

    //myメモ登録
    public static function createNote($data)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {
            $error_code = 0;
            if(!isset($data['user_id']))    $error_code = 1;   //データ不足
            if(!isset($data['title']))      $error_code = 2;   //データ不足
            //if(!isset($data['color_num']))  $error_code = 3;   //データ不足
            
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
    
    //myメモ削除
    public static function delNote($data)
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

    //myメモ変更
    public static function chgNote($data) 
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");
        try {

            $note = Note::where('id', $data['id'])->first();
            if(!$note){
                make_error_log($error_log,".not found id:".$data['id']);
                return ['id' => null, 'error_code' => -1];   //更新失敗
            }
            if($note->user_id != Auth::id()){
                //ここで共有メモなら許可する
                if($data['share_flag']){
                    make_error_log($error_log,".shared item's user_id:".$note->user_id." current user:".Auth::id());
                }else{
                    make_error_log($error_log,".user_id:".$note->user_id." user's item");
                    return ['id' => null, 'error_code' => -2];   //更新失敗
                }
            }
            if($note->edit_lock_flag && $data['edit_lock_flag']){
                make_error_log($error_log,".this item is locked");
                return ['id' => null, 'error_code' => -3];   //更新失敗
            }
            // 更新対象となるカラムと値を連想配列に追加
            $updateData = [];   
            if(isset($data['title']))           $updateData['title']            = $data['title']; 
            if(isset($data['content']))         $updateData['content']          = $data['content'];
            if(isset($data['color_num']))       $updateData['color_num']        = $data['color_num'];
            if(isset($data['edit_lock_flag']))  $updateData['edit_lock_flag']   = $data['edit_lock_flag'];

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

