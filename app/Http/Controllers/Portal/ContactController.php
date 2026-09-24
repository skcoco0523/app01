<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\UserRequest;

class ContactController extends Controller
{
    /**
     * 未認証向け お問い合わせフォーム表示
     */
    public function index(Request $request)
    {
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        return view('portal.contact.index', compact('input'));
    }

    /**
     * 未認証向け お問い合わせ送信処理
     */
    public function send(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-----start-----");
        $input = $request->all();
        
        // 未ログイン時は 0（ゲスト）として登録
        $params['user_id']  = Auth::id();
        $params['type']     = get_proc_data($input, "type");
        $params['message']  = get_proc_data($input, "message");
        // 未ログイン（ゲスト）の場合のみ、フォームに入力された email を取得
        if (empty($params['user_id'])) {
            $params['email'] = get_proc_data($input, "email");
        } else {
            $params['email'] = null; // ログイン済みなら users テーブルから引けるため null
        }
        
        make_error_log($error_log, "user_id:".$params['user_id']."    email:".$params['email']."    type:".$params['type']."    message:".$params['message']);

        $ret = UserRequest::createRequest($params);
        make_error_log($error_log, "error_code:".$ret['error_code']);

        $message = make_message('送信に失敗しました。', 'error');
        if($ret['error_code'] == 0){       
            $message = make_message('お問い合わせを送信しました。', 'send'); 
        }

        return redirect()->route('contact.index')->with($message);
    }
}