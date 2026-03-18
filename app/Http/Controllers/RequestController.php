<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\UserRequest;


class RequestController extends Controller
{
    //ユーザーリクエスト
    public function index(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        //データチェック
        $input['user_id']           = Auth::id();
        $input['page']              = get_proc_data($input,"page");

        $user_request = UserRequest::getRequestList(10,true,$input['page'],$input);  //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ
        return view('user.request_show', compact('user_request', 'input'));
    }
    //リクエスト送信
    public function send(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        $input = $request->all();
        
        //データチェック
        $input['user_id']           = Auth::id();
        $input['type']              = get_proc_data($input,"type");
        $input['message']           = get_proc_data($input,"message");
        make_error_log($error_log,"user_id:".$input['user_id']. "    type:".$input['type']. "    message:".$input['message']);

        $ret = UserRequest::createRequest($input);
        make_error_log($error_log,"error_code:".$ret['error_code']);

        $message = make_message('送信に失敗しました。', 'error');
        if($ret['error_code'] == 0){       
            $message = make_message('送信しました。', 'send'); 
        }
        return redirect()->route('request.index')->with($message);
    }

    

}
