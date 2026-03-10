<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\Note;
use App\Models\NoteShare;

class NoteController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //ユーザーのみ
        $this->middleware('auth');
    }

    //メモ一覧ページ
    public function index(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        $input['admin_flag']    = false;
        $input['color_num_asc'] = true;
        //$input['name_asc']      = true;
        //アクセス数を減らすため、ページング、カラー選択は画面側で対応する

        //所有メモ
        //$input['search_color_num']  = get_proc_data($input,"mynote_color_num");
        $my_note_list               = Note::getNoteList(null,false,null,$input);  //全件

        //共有されたメモ
        //$input['search_color_num']  = get_proc_data($input,"share_note_color_num");
        $share_note_list            = NoteShare::getSharedNoteList(null,false,null,$input);  //全件

        // 2. ナビ用の「色ごとの件数」を集計
        $my_note_counts     = $my_note_list->groupBy('color_num')->map->count();
        $share_note_counts  = $share_note_list->groupBy('color_num')->map->count();

        //ナビからカラーを選択せずに表示した場合の初期値設定
        //$my_note_select_color       = $my_note_list->first()->color_num ?? 0;
        //$share_note_select_color    = $share_note_list->first()->color_num ?? 0;

        $msg = null;
        //if($my_note_list){
            return view('note.show', compact('my_note_list', 'share_note_list', 'my_note_counts', 'share_note_counts', 'msg'));
        //}else{
            //return redirect()->route('home')->with('error', '該当の曲が存在しません');
        //}
    }
    //メモ詳細ページ
    public function show(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']        = false;
        $input['search_note_id']    = $id;
        $input['share_flag']        = get_proc_data($input,"share_flag");

        if($input['share_flag']){
            $note = NoteShare::getSharedNoteList(1,true,false,$input)->first();  //1件
        }else{
            $note = Note::getNoteList(1,true,false,$input)->first();  //1件
        }

        if ($note !== null) {
            //dd($note);
            $msg = null;
            return view('note.detail', compact('note', 'input', 'msg'));

        }else{
            //使用不可のため強制リダイレクト
            return redirect()->route('home');
        }
    }

    //メモ登録
    public function store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        $user_id                    = Auth::id();
        $input['user_id']           = $user_id;
        $input['title']             = get_proc_data($input,"title");
        $input['content']           = get_proc_data($input,"content");
        $input['color_num']         = get_proc_data($input,"color_num");
        //$input['edit_lock_flag'] = $edit_lock_flag;
        $input['edit_lock_flag']    = false;   //初期値は編集不可ロックをかけない

        make_error_log($error_log,"user_id:".$user_id);
        make_error_log($error_log,"title:".$input['title']. "    color_num:".$input['color_num']);

        $ret = Note::createNote($input);

        $msg = null;
        if($ret['error_code']==0){
            $msg = "メモを追加しました。";          $type = "note_add";
        }else{
            $msg = "メモの追加に失敗しました。";    $type = "error";
        }                        

        $message = ['message' => $msg, 'type' => $type, 'sec' => '2000'];
        make_error_log($error_log,"msg:".$msg);

        // メモ作成成功時は詳細ページへ、失敗時は一覧ページへ
        if($ret['error_code']==0){
            return redirect()->route('note.show', ['id' => $ret['id']])->with($message);
        }else{
            return redirect()->route('note.index')->with($message);
        }

    }
    //メモ変更
    public function update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']        = false;
        $input['id']                = get_proc_data($input,"id");
        $input['title']             = get_proc_data($input,"title");
        $input['content']           = get_proc_data($input,"content");
        $input['color_num']         = get_proc_data($input,"color_num");
        $input['share_flag']        = get_proc_data($input,"share_flag");

        //$ret = Note::chgNote(['id'=>$input['id'], 'title'=>$input['title'], 'content'=>$input['content'], 'color_num'=>$input['color_num']]);
        $ret = Note::chgNote($input);

        if($ret['error_code']==0){          $msg = "更新しました。";            $type = "note_chg"; }
        else if($ret['error_code']==-2){    $msg = "更新権限がありません。";    $type = "error"; }
        else if($ret['error_code']==-3){    $msg = "更新ロック状態です。";      $type = "error"; }
        else{                               $msg = "更新に失敗しました。";      $type = "error"; }

        $message = ['message' => $msg, 'type' => $type, 'sec' => '2000'];

        //リダイレクトでGETの許容オーバーを防ぐため、内容はクリアしておく
        $input['content'] = null; //内容は不要なのでクリア

        return redirect()->route('note.show', ['id' => $input['id']])->with($message);

    }
    //メモ削除
    public function destroy(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']        = false;
        $input['search_note_id']    = get_proc_data($input,"id");

        $note = Note::getNoteList(1,true,false,$input)->first();  //1件
        
        //dd($note,$input);
        //所有者のみ削除可能
        if($note){
            $ret = Note::delNote(['id'=>$note->id]);
            if($ret['error_code']==0){
                $msg = "削除しました。";        $type = "note_del";
            }else{
                $msg = "削除に失敗しました。";  $type = "error";
            }    
        }else{
            $msg = "所有者のみ削除可能です。";  $type = "error";
        }               

        $message = ['message' => $msg, 'type' => $type, 'sec' => '2000'];

        return redirect()->route('note.index')->with($message);

    }
    //メモ共有      APIで処理
    //メモ共有解除  APIで処理

    //メモ共有解除　共有された側のユーザーからの操作で削除する
    public function unshare(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();  

        $input['search_note_id']    = get_proc_data($input,"note_id");
        $sharing_note               = NoteShare::getSharedNoteList(null,false,null,$input)->first();  
        $input['id']                = $sharing_note->note_share_id;

        if($sharing_note->user_id == Auth::id()){
            $ret = NoteShare::delShareNote($input);
            if($ret['error_code']==0){
                $msg = "削除しました。";        $type = "note_del";
            }else{
                $msg = "削除に失敗しました。";  $type = "error";
            }    
        }else{
            $msg = "共有されている場合のみ削除可能です。";  $type = "error";
        }   
        $message = ['message' => $msg, 'type' => $type, 'sec' => '2000'];
        return redirect()->route('note.index')->with($message);
    }
}