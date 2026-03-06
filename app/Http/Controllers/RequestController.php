<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\UserRequest;


class RequestController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
    }
    //ユーザーリクエスト
    public function show(Request $request)
    {
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        $input['type']              = get_proc_data($input,"type");
        $input['message']           = get_proc_data($input,"message");
        $input['page']              = get_proc_data($input,"page");
        $user_id = Auth::id();
        if($user_id){
            $user_request = UserRequest::getRequestList(10,true,$input['page'],['login_id' => $user_id]);  //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ
            $msg = request('msg');

            return view('user.request_show', compact('user_request', 'input', 'msg'));
        }else{
            return redirect()->route('home')->with('error', '再度ログインしてください。');
        }
    }
    //リクエスト送信
    public function send(Request $request)
    {
        $input = $request->all();
        
        //データチェック
        $input['user_id']           = Auth::id();
        $input['type']              = get_proc_data($input,"type");
        $input['message']           = get_proc_data($input,"message");

        $ret = UserRequest::createRequest($input);
        $msg = null;;

        //htmlで必須としているけど念のため
        if($ret['error_code'] == 3) $msg = "メッセージは必須情報です。";
        if($msg) return redirect()->route('request.show')->with($msg);


        if($ret['error_code'] == 0){        
            //$profile = Auth::user();
            //dd($profile);
            $message = ['message' => '送信しました。',
                        'type' => 'send',
                        'sec' => '2000'];
            return redirect()->route('request.show')->with($message);
        }else{
            $message = ['message' => '送信に失敗しました。',
                        'type' => 'error',
                        'sec' => '2000'];
            return redirect()->route('request.show')->with($message);
        }
    }

    

}
