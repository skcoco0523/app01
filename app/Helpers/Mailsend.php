<?php

//namespace App\Helpers;

// app/Helpers/Mail_send.php
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\HtmlString;
use App\Jobs\SendMailJob;
use App\Models\User;

class MailContent extends Mailable
{
    //protected $mailMessage;

    public function __construct($mailMessage)
    {
        $this->view(['html' => $mailMessage->render()])
        ->subject($mailMessage->subject);
    }

}

//テンプレートからメッセージ取得
/*  使用例
    //ユーザーへ登録完了メール送信
    $send_info = new \stdClass();
    $send_info->name = $request->name;
    $mess = get_MailMessage($send_info, "user_reg");
    mail_send($send_info, $mess, $mail=null, true); //管理者全員へ送信
    mail_send($send_info, $mess, $request->email);  //特定ユーザーへ送信

*/

//メール送信関数　テンプレート内データ,送信先
if (! function_exists('mail_send')) {
    function mail_send($send_info, $mess, $mail=null, $admin_flag = false){
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "========================start========================");
        make_error_log($error_log, "mail: ".$mail. "  admin_flag: ".$admin_flag);
        if ($mess) {
            $mailable = new MailContent($mess);
            //dd($mailable);
            //即時実行
            //Mail::to($mail)->send($mailable);
            
            //管理者充て(admin_flag=ture)の場合は複数名に送信するため、一旦配列にする
            $send_mail_list=array();
            if($mail){
                $send_mail_list[0] = $mail;
            }elseif($admin_flag){
                $user_list = User::getUserList(100,false,null,['search_admin_flag' => true]);
                foreach($user_list as $user){
                    if($user && $user->email != null)  $send_mail_list[] = $user->email;
                }
            }else{
                make_error_log($error_log, "mail and admin_flag are null");
                return;
            }

            foreach($send_mail_list as $mail){
                // SendMailJobをディスパッチしてバックグラウンドで実行
                SendMailJob::dispatch($mail,$mailable);
            }   
        }else{
            make_error_log($error_log,"not_tmpl");
        }
    }
}

if (! function_exists('get_MailMessage')) {
    function get_MailMessage($send_info, $tmpl)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        //app01\vendor\laravel\framework\src\Illuminate\Notifications\Messages\MailMessage.php
        switch($tmpl){
            //==============================================================================================
            //ユーザー充て
            //==============================================================================================
            case 'password_reset':  //パスワードリセットメール
                $MailMessage = (new MailMessage)
                    ->markdown('emails.mail')
                    ->subject(Lang::get('パスワードリセット'))
                    ->line(Lang::get('本メールはパスワードリセットのご案内です。'))
                    ->action(Lang::get('リセットはこちらから'), url(config('app.url').route('password.reset', ['token' => $send_info->token, 'email' => $send_info->mail], false)))
                    ->line(Lang::get('このパスワード リセット リンクは :count 分後に期限切れになります。', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
                    ->line(Lang::get('もしパスワード再発行をリクエストしていない場合、操作は不要です。'))
                    ->line(Lang::get('************************************'))
                    ->line(Lang::get('問い合わせ先:skcoco.05.23@gmail.com'))
                    ->line(Lang::get('************************************'));

                return $MailMessage;
                break;
                
            case 'email_verify':  //メールアドレス認証メール
                // $send_infoの中に 'url' を含めて渡す想定
                $MailMessage = (new MailMessage)
                    ->markdown('emails.mail')
                    ->subject(Lang::get('メールアドレスの確認'))
                    ->line(Lang::get('会員登録ありがとうございます。下のボタンをクリックして、メールアドレスの認証を完了させてください。'))
                    ->action(Lang::get('メールアドレスを認証する'), $send_info->url) // 生成したURLをセット
                    ->line(Lang::get('もし心当たりがない場合は、このメールを破棄してください。'))
                    ->line(Lang::get('************************************'))
                    ->line(Lang::get('問い合わせ先:skcoco.05.23@gmail.com'))
                    ->line(Lang::get('************************************'));

                return $MailMessage;
                break;
                    
            case 'user_reg':    //ユーザー登録完了メール
                $MailMessage = (new MailMessage)
                ->markdown('emails.mail')
                ->subject(Lang::get('会員登録のお知らせ'))
                ->line(Lang::get($send_info->name.'様'))
                ->line(Lang::get('この度は会員のご登録ありがとうございます。'))
                ->line(Lang::get('本メールは登録された時点で送信される自動配信メールです。'))
                ->line(Lang::get('メールが不要の場合は、配信停止設定をお願いいたします。'))
                ->action(Lang::get('配信設定はこちらから'), url(route('profile.show')))
                ->line(Lang::get('************************************'))
                ->line(Lang::get('問い合わせ先:skcoco.05.23@gmail.com'))
                ->line(Lang::get('************************************'));

                return $MailMessage;
                break;


            //==============================================================================================
            //ここからは管理者充て
            //==============================================================================================
            case 'user_reg_notice':   //ユーザー登録通知メール
                $MailMessage = (new MailMessage)
                ->markdown('emails.mail')
                ->subject(Lang::get('【SK_HOME:管理者】ユーザー登録通知'))
                ->line(Lang::get('新規登録者名:'. $send_info->user_name))
                ->line(Lang::get('現在ユーザー数:'. $send_info->now_user_cnt));

                return $MailMessage;
                break;

            default:
                return null;
        }
    }
}
