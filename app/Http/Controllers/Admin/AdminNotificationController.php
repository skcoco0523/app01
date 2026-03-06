<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

use App\Models\User;

class AdminNotificationController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    //通知メニュー
    public function notification(Request $request)
    {
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']            = true;
        $input['search_name']           = get_proc_data($input,"search_name");
        $input['search_email']          = get_proc_data($input,"search_email");
        $input['search_friendcode']     = get_proc_data($input,"search_friendcode");
        $input['search_gender']         = get_proc_data($input,"search_gender");
        $input['search_release_flag']   = get_proc_data($input,"search_release_flag");
        $input['search_mail_flag']      = get_proc_data($input,"search_mail_flag");
        $input['search_admin_flag']     = get_proc_data($input,"search_admin_flag");

        $input['send_target']           = get_proc_data($input,"send_target");
        $input['send_type']             = get_proc_data($input,"send_type");

        $input['page']                  = get_proc_data($input,"page");

        $user_list=null;
        if($input['send_target']==0){           //一般ユーザー
            $input['search_admin_flag'] = false;

        }elseif($input['send_target']==1){      //管理者
            $input['search_admin_flag'] = true;

        }elseif($input['send_target']==2){      //指定ユーザー
        }
        $user_list = User::getUserList(15,true,$input['page'],$input);    //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ,sort(1:,2:,3:,4:)

        $msg = request('msg');


        return view('admin.admin_home', compact('user_list', 'input', 'msg'));
    }

    //メール通知
    public function admin_mail_send(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        
        $input = $request->all();
        $input['search_name']           = get_proc_data($input,"search_name");
        $input['search_email']          = get_proc_data($input,"search_email");
        $input['search_friendcode']     = get_proc_data($input,"search_friendcode");
        $input['search_gender']         = get_proc_data($input,"search_gender");
        $input['search_release_flag']   = get_proc_data($input,"search_release_flag");
        $input['search_mail_flag']      = get_proc_data($input,"search_mail_flag");
        
        $input['send_target']           = get_proc_data($input,"send_target");
        if($input['send_target']==0){           //一般ユーザー
            $input['search_admin_flag'] = false;

        }elseif($input['send_target']==1){      //管理者
            $input['search_admin_flag'] = true;

        }elseif($input['send_target']==2){      //指定ユーザー
            $input['search_admin_flag']      = get_proc_data($input,"search_admin_flag");
        }


        $input['title']                 = get_proc_data($input,"title");
        $input['content']               = get_proc_data($input,"content");
        $msg=null;
        //dd($input);

        try {
            //メール内容定義
            $mess = (new MailMessage)
                ->markdown('emails.mail')
                ->subject(Lang::get($input['title']))
                ->line(Lang::get($input['content']));

            $user_list = User::getUserList(1000,false,null,$input);    //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ,sort(1:,2:,3:,4:)
            //dd($mess,$user_list);

            //該当ユーザーへ送信
            foreach($user_list as $user){
                $send_info = new \stdClass();
                mail_send($send_info, $mess, $user->email);
            }
            

        } catch (\Exception $e) {
            //make_error_log($error_log, "Error Message: " . $e->getMessage());
        }

        return redirect()->route('admin-notification', ['input' => $input, 'msg' => $msg]);

    }
    //プッシュ通知
    public function admin_push_send(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        
        $input = $request->all();
        $input['search_name']           = get_proc_data($input,"search_name");
        $input['search_email']          = get_proc_data($input,"search_email");
        $input['search_friendcode']     = get_proc_data($input,"search_friendcode");
        $input['search_gender']         = get_proc_data($input,"search_gender");
        $input['search_release_flag']   = get_proc_data($input,"search_release_flag");
        $input['search_mail_flag']      = get_proc_data($input,"search_mail_flag");
        
        $input['send_target']           = get_proc_data($input,"send_target");
        if($input['send_target']==0){           //一般ユーザー
            $input['search_admin_flag'] = false;

        }elseif($input['send_target']==1){      //管理者
            $input['search_admin_flag'] = true;

        }elseif($input['send_target']==2){      //指定ユーザー
            $input['search_admin_flag']      = get_proc_data($input,"search_admin_flag");
        }


        $input['title']                 = get_proc_data($input,"title");
        $input['route']                 = get_proc_data($input,"route");
        $input['content']               = get_proc_data($input,"content");
        $msg=null;

        try {

            $user_list = User::getUserList(1000,false,null,$input);    //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ,sort(1:,2:,3:,4:)

            //dd($input,$user_list);

            //該当ユーザーへ送信
            foreach($user_list as $user){
                $send_info = new \stdClass();
                $send_info->title = $input['title'];
                $send_info->body = $input['content'];
                if($input['route']){
                    $send_info->url = route($input['route']);
                }
                push_send($send_info, $user->id);
            }

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
        }

        return redirect()->route('admin-notification', ['input' => $input, 'msg' => $msg]);

    }
    

}
