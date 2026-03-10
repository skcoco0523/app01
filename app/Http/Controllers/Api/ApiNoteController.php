<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;

use App\Models\Note;
use App\Models\NoteShare;

class ApiNoteController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    
    
    public function api_note_manage(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $input                  = $request->all(); 
        $type = $request->route('type');
        make_error_log($error_log,"type:".$type);
        //所有者チェック
        $input['search_note_id']    = get_proc_data($input,"note_id");
        $note                       = Note::getNoteList(null,false,null,$input)->first();
        if(!$note) return false;

        //共有済みチェック
        $input['search_note_id']    = get_proc_data($input,"note_id");
        $friend_id                  = get_proc_data($input,"friend_id");

        $input['search_friend_id']  = $friend_id;
        $sharing_note               = NoteShare::getSharingNoteList(null,false,null,$input)->first();

        if($type == 'share'         && $sharing_note) return false;
        if($type == 'unshare'       && !$sharing_note) return false;
        if($type == 'enable_edit'   && !$sharing_note) return false;
        if($type == 'disable_edit'  && !$sharing_note) return false;
    
        $input['user_id']       = get_proc_data($input,"friend_id");
        $input['note_id']       = get_proc_data($input,"note_id");
        $input['note_share_id'] = get_proc_data($input,"note_share_id");
    
        if($type == 'share'){
            $ret = NoteShare::createShareNote($input);
            if($ret['error_code'] == 0){
                make_error_log($error_log,"Shared successfully.");
                //共有成功時は共有者に通知
                $send_info = new \stdClass();
                $send_info->title = "メモが共有されました";
                $send_info->body = "共有者：".Auth::user()->name."\nメモ:". $note->title;
                $send_info->url = route('note.detail', ['id' => $note->id, 'share_flag' => 1]);
                
                push_send($send_info, $friend_id);

            }else{
                make_error_log($error_log,"Failed to share. error_code=".$ret['error_code']);
            }
        }elseif($type == 'unshare'){
            $input['id']       = $sharing_note->note_share_id;
            NoteShare::delShareNote($input);
        }elseif($type == 'enable_edit'){
            $input['id']            = $sharing_note->note_share_id;
            $input['admin_flag']    = true;
            NoteShare::chgShareNote($input);
            
        }elseif($type == 'disable_edit'){
            $input['id']            = $sharing_note->note_share_id;
            $input['admin_flag']    = false;
            NoteShare::chgShareNote($input);
        }
        return true;
            
    }

}
