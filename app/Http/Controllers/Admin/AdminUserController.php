<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;

//ユーザーコントローラー
class AdminUserController extends Controller
{
    //ユーザー検索
    public function index(Request $request)
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
    public function destroy(Request $request)
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
    public function update(Request $request)
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
            return redirect()->route('admin.user.index', ['input' => $input, 'msg' => $msg]);
        }

        return redirect()->route('admin.user.index', ['input' => $input, 'msg' => $msg]);
    }
    
}
