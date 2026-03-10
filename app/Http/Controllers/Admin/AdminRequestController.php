<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\UserRequest;

//ユーザーコントローラー
class AdminRequestController extends Controller
{
    
    //ユーザーリクエスト検索
    public function index(Request $request)
    {
        //変更 or 削除からのリダイレクトの場合、inputを取得
        if($request->input('input')!==null)         $input = request('input');
        else                                        $input = $request->all();
        
        $input['admin_flag']            = true;
        $input['search_type']           = get_proc_data($input,"search_type");
        $input['search_status']         = get_proc_data($input,"search_status");
        $input['search_mess']           = get_proc_data($input,"search_mess");
        $input['search_reply']          = get_proc_data($input,"search_reply");

        $input['page']                  = get_proc_data($input,"page");

        $user_request = UserRequest::getRequestList(10,true,$input['page'],$input);  //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ
        $msg = request('msg');
        //dd($user_request);
        
        return view('admin.admin_home', compact('user_request', 'input', 'msg'));
    }
    //ユーザーリクエスト更新
    public function update(Request $request)
    {
        $input = $request->all();
        $input['admin_flag']            = true;
        $input['id']                    = get_proc_data($input,"id");
        $input['type']                  = get_proc_data($input,"type");
        $input['message']               = get_proc_data($input,"message");
        $input['reply']                 = get_proc_data($input,"reply");
        $input['status']                = get_proc_data($input,"status");
        $input['notification_flag']     = get_proc_data($input,"notification_flag");

        //dd($input);
        $msg = null;
        if(!isset($input['id']))        $msg =  "対象が選択されていません。";
        //if(!isset($input['message']))   $msg =  "依頼内容は必須情報です。";
        //if(!isset($input['reply']))     $msg =  "回答内容は必須情報です。";       null更新有にする
        if(!isset($input['status']))    $msg =  "ステータスは必須情報です。";
        
        if(!$msg){
            $ret = UserRequest::chgRequeste($input);
            if($ret['error_code'] == 0)     $msg = "更新しました。";
            else                            $msg = "更新に失敗しました。";
        }else{
            return redirect()->route('admin.user.request.index', ['input' => $input, 'msg' => $msg]);
        }

        //対象のユーザーへ通知する
        if($input['notification_flag']){
            if($ret['error_code'] == 0 && $ret['user_id']){
                 
                $send_info = new \stdClass();
                $send_info->title = "ご質問への回答があります";
                $send_info->body = $input['reply'];
                $send_info->url = route('request.show');
                
                push_send($send_info, $ret['user_id']);
            }
        }

        return redirect()->route('admin.user.request.index', ['input' => $input, 'msg' => $msg]);

    }

}
