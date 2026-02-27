<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Auth\VerifiesEmails;

class VerificationController extends Controller
{
    use VerifiesEmails;
    public function __construct()
    {
        //routeで設定するため、ここではコメントアウト
        //$this->middleware('signed')->only('verify');
        //$this->middleware('auth');
        //$this->middleware('throttle:6,1')->only('verify', 'send');
    }

    //認証メール送信
    public function send(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            $message = ['message' => 'メール認証はすでに完了しています。','type' => '','sec' => '2000'];
            return redirect()->route('home')->with($message);
        }
        // ==========================================================
        // 署名付きURLの生成
        // ==========================================================
        // web.php で定義した名前
        $verification_route="verification.verify";
        $expiry_minutes=60; // 有効期限（60分など）

        $verificationUrl = URL::temporarySignedRoute(
            $verification_route,
            Carbon::now()->addMinutes($expiry_minutes),
            ['id' => $user->getKey(), 'hash' => sha1($user->getEmailForVerification())]
        );
        // ==========================================================
        
        //所有者が確定しているため接続通知
        $send_info = new \stdClass();
        $send_info->url = $verificationUrl;
        $mess = get_MailMessage($send_info, "email_verify");
        mail_send($send_info, $mess, $user->email);

        $message = ['message' => '認証用メールを送信しました。','type' => '','sec' => '2000'];
        return back()->with($message);
        // return redirect()->route('home')->with($message);
    }

}
