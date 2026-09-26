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

    /**
     * Lineログイン画面を表示
     *
     * @param \Illuminate\Http\Request|null $request
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function lineLogin(?Request $request = null)
    {
        // $request が null の場合は helper から取得
        $request = $request ?? request();
        $action = $request->input('action', 'login');
        
        // stateに action 情報を埋め込む（例: login_xxxx または register_xxxx）
        $state = $action . '_' . Str::random(32);
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

        // 【修正箇所1】先に state パラメータから action を復元する
        $state = $request->input('state');
        $action = 'login';
        if ($state && str_starts_with($state, 'register_')) {
            $action = 'register';
        }

        // 認証エラーがあれば再ログイン
        if ($request->has('error')) {
            $retry_cnt = session('login_retry_count', 0);
            make_error_log($error_log, "error_description=" . json_encode($request->error_description));

            if ($retry_cnt >= 5) {
                make_error_log($error_log, "login_retry_count=".$retry_cnt);
                session()->forget('login_retry_count');
                return redirect()->route('home')->with('message', 'ログインに失敗しました。');
            }

            session(['login_retry_count' => $retry_cnt + 1]);

            // 【修正箇所2】action 情報を引き継いで再ログインへ
            $request->merge(['action' => $action]);
            return $this->lineLogin($request);
        }

        $accessToken = $this->getAccessToken($request);
        $profile = $this->getProfile($accessToken);

        // ユーザー情報あるか確認
        $user = User::where('line_id', $profile->userId)->first();

        // あったらログイン
        if ($user) {
            // 新規登録フローなのにすでに登録されている場合
            if ($action === 'register') {
                return redirect()->route('register')->with('line_error', 'このLINEアカウントはすでに登録されています。ログインしてください。');
            }

            Auth::login($user, true); 
            UserLog::create_user_log(Auth::id(), "line_login");

        // なければ登録してからログイン
        } else {
            // ログインフローからの場合は、新規登録を行わずにログイン画面へ戻す
            if ($action === 'login') {
                return redirect()->route('login')->with('line_error', 'このLINEアカウントは登録されていません。新規登録を行ってください。');
            }

            $user = new User();
            $user->provider = 'line';
            $user->line_id = $profile->userId;
            $user->name = $profile->displayName;
            $user->friend_code = User::generateUniqueFriendCode();
            $user->email_verified_at = now();
            $user->save();

            Auth::login($user, true); 
            UserLog::create_user_log(Auth::id(), "line_user_reg");
            UserLog::create_user_log(Auth::id(), "line_login");

            // 通知処理...
            $now_user_cnt = User::count();

            $send_info = new \stdClass();
            $send_info->user_name = $profile->displayName;
            $send_info->now_user_cnt = $now_user_cnt;
            $mess = get_MailMessage($send_info, "user_reg_notice");
            mail_send($send_info, $mess, $mail=null, true);

            $send_info = new \stdClass();
            $send_info->title = "新規ユーザー登録";
            $send_info->body = "ユーザー名：".$profile->displayName."\n現在ユーザー数:". $now_user_cnt;
            $send_info->url = route('admin.user.index');

            push_send($send_info, null, true);
        }

        return redirect('/');
    }
    

}
