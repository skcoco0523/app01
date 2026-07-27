<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

use App\Models\User; 
use App\Models\UserLog; 

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class LineLoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // LINE Developers: https://developers.line.biz/ja/

    // Lineログイン画面を表示
    public function lineLogin()
    {
        $state = Str::random(32);
        $nonce  = Str::random(32);

        $uri = "https://access.line.me/oauth2/v2.1/authorize?";
        $uri .= "response_type=code";
        $uri .= "&client_id=" . config('services.line.client_id');
        $uri .= "&redirect_uri=" . config('services.line.redirect');
        $uri .= "&state=" . $state;
        $uri .= "&scope=openid%20profile";
        $uri .= "&nonce=" . $nonce;

        return redirect($uri);
    }

    // アクセストークン取得
    public function getAccessToken($req)
    {

        $headers = [ 'Content-Type: application/x-www-form-urlencoded' ];
        $post_data = array(
            'grant_type'    => 'authorization_code',
            'code'          => $req['code'],
            'redirect_uri'  => config('services.line.redirect'),
            'client_id'     =>  config('services.line.client_id'),
            'client_secret' => config('services.line.client_secret'),
        );
        $url = 'https://api.line.me/oauth2/v2.1/token';

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));

        $res = curl_exec($curl);
        curl_close($curl); 
        $json = json_decode($res);
        
        $accessToken = $json->access_token;

        return $accessToken;
    }

    // プロフィール取得
    public function getProfile($at)
    {

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $at));
        curl_setopt($curl, CURLOPT_URL, 'https://api.line.me/v2/profile');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $res = curl_exec($curl);
        curl_close($curl);

        $json = json_decode($res);
        //dd($json);
        return $json;

    }

    // ログイン後のページ表示
    public function callback(Request $request)
    {
        $error_log = "linelogin.log";
        // 認証エラーがあれば再ログイン
        if ($request->has('error')) {
            // セッションにエラーカウントがあるか確認
            $retry_cnt = session('login_retry_count', 0); // デフォルト0
            make_error_log($error_log, "error_description=" . json_encode($request->error_description));

            // 5回以上エラーが発生していたら、homeへリダイレクト
            if ($retry_cnt >= 5) {
                make_error_log($error_log, "login_retry_count=".$retry_cnt);
                session()->forget('login_retry_count'); // カウントをリセット
                return redirect()->route('home')->with('message', 'ログインに失敗しました。');
            }

            // カウントを増やしてセッションに保存
            session(['login_retry_count' => $retry_cnt + 1]);

            // エラーのため、再度ログインを試す
            return $this->lineLogin();
        }
        $accessToken = $this->getAccessToken($request);
        $profile = $this->getProfile($accessToken);

        // ユーザー情報あるか確認
        $user=User::where('line_id', $profile->userId)->first();

        // あったらログイン
        if($user) {
            // 第二引数(remember)を使ってログイン
            //Auth::login($user);
            Auth::login($user, true); 
            UserLog::create_user_log(Auth::id(),"line_login");

        // なければ登録してからログイン
        }else {
            $user=new User();
            $user->provider='line';
            $user->line_id=$profile->userId;
            $user->name=$profile->displayName;
            $user->friend_code = User::generateUniqueFriendCode();
            $user->email_verified_at = now();   // メール認証を「現在時刻」で完了状態にする
            $user->save();
            // 第二引数(remember)を使ってログイン
            //Auth::login($user);
            Auth::login($user, true); 
            UserLog::create_user_log(Auth::id(),"line_user_reg");
            UserLog::create_user_log(Auth::id(),"line_login");

            //自身に通知する
            $now_user_cnt = User::count();
    
            $send_info = new \stdClass();
            $send_info->user_name = $profile->displayName;
            $send_info->now_user_cnt = $now_user_cnt;
            $mess = get_MailMessage($send_info, "user_reg_notice");
            mail_send($send_info, $mess, $mail=null, true); //管理者全員へ送信
             
            $send_info = new \stdClass();
            $send_info->title = "新規ユーザー登録";
            $send_info->body = "ユーザー名：".$profile->displayName."\n現在ユーザー数:". $now_user_cnt;
            $send_info->url = route('admin-user-search');

            push_send($send_info, null, true); //管理者全員へ送信
            
        }

        //return redirect('/home/?login=success');
        return redirect('/');
        
    }
    

}
