<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\UserRequest;

//ユーザーコントローラー
class AdminUserController extends Controller
{
    //ユーザー検索
    public function user_search(Request $request)
    {
        //変更 or 削除からのリダイレクトの場合、inputを取得
        if($request->input('input')!==null)         $input = request('input');
        else                                        $input = $request->all();
        
        $input['admin_flag']                = true;
        $input['search_name']               = get_proc_data($input,"search_name");
        $input['search_email']              = get_proc_data($input,"search_email");
        $input['search_friendcode']         = get_proc_data($input,"search_friendcode");
        $input['search_gender']             = get_proc_data($input,"search_gender");
        $input['search_release_flag']       = get_proc_data($input,"search_release_flag");
        $input['search_mail_flag']          = get_proc_data($input,"search_mail_flag");
        $input['search_admin_flag']         = get_proc_data($input,"search_admin_flag");
        $input['search_dev_reg_lock_flag']  = get_proc_data($input,"search_dev_reg_lock_flag");

        $input['page']                  = get_proc_data($input,"page");

        $user_list = User::getUserList(15,true,$input['page'],$input);    //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ,sort(1:,2:,3:,4:)
        //dd($user_list);
        $msg = request('msg');
        
        return view('admin.admin_home', compact('user_list', 'input', 'msg'));
    }
    //削除
    /*
    public function user_del(Request $request)
    {
        $input = $request->all();
        //$msg = Music::delMusic($data['id']);
        $ret = Music::delMusic($input['id']);
        if($ret['error_code']==0)     $msg = "曲：". $input['name']. "を削除しました。";
        if($ret['error_code']==-1)    $msg = "曲の削除に失敗しました。";

        return redirect()->route('admin-music-search', ['input' => $input, 'msg' => $msg]);
    }
    */
    //ユーザー変更
    public function user_chg(Request $request)
    {
        $input = $request->all();
        $input['admin_flag']        = true;
        $input['name']              = get_proc_data($input,"name");
        $input['email']             = get_proc_data($input,"email");
        $input['birthdate']         = get_proc_data($input,"birthdate");
        $input['prefectures']       = get_proc_data($input,"prefectures");
        $input['gender']            = get_proc_data($input,"gender");
        $input['release_flag']      = get_proc_data($input,"release_flag");
        $input['mail_flag']         = get_proc_data($input,"mail_flag");
        $input['dev_reg_lock']      = get_proc_data($input,"dev_reg_lock");

        $msg = null;
        if(!isset($input['name']))              $msg =  "ユーザー名は必須情報です。";
        if(!isset($input['email']))             $msg =  "emailは必須情報です。";
        if(!isset($input['birthdate']))         $msg =  "生年月日は必須情報です。";
        if(!isset($input['prefectures']))       $msg =  "都道府県は必須情報です。";
        if(!isset($input['gender']))            $msg =  "性別は必須情報です。";
        if(!isset($input['release_flag']))      $msg =  "公開状態は必須情報です。";
        if(!isset($input['mail_flag']))         $msg =  "ﾒｰﾙ送信ﾌﾗｸﾞは必須情報です。";
        if(!isset($input['dev_reg_lock']))      $msg =  "ﾃﾞﾊﾞｲｽ追加権限は必須情報です。";
        
        if(!$msg){
            $ret = User::chgProfile($input);
            if($ret['error_code'] == 0)     $msg = "プロフィール情報を更新しました。";
            else                            $msg = "プロフィール情報の更新に失敗しました。";
        }else{
            return redirect()->route('admin-user-search', ['input' => $input, 'msg' => $msg]);
        }

        return redirect()->route('admin-user-search', ['input' => $input, 'msg' => $msg]);
    }
    
    //ユーザーリクエスト検索
    public function user_request_search(Request $request)
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
    public function user_request_chg(Request $request)
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
            return redirect()->route('admin-request-search', ['input' => $input, 'msg' => $msg]);
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

        return redirect()->route('admin-request-search', ['input' => $input, 'msg' => $msg]);

    }

}
