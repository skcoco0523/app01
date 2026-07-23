<?php

//namespace App\Helpers;

use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

use App\Models\User;
use App\Models\UserDevice;
use App\Models\UserLog; 
// app/Helpers/PushNotification.php

/*使用例
    $send_info = new \stdClass();
    $send_info->title = "新規ユーザー登録";
    $send_info->body = "ユーザー名：".$request->name."\n現在ユーザー数:". $now_user_cnt;
    $send_info->url = route('admin.user.index');
    
    push_send($send_info, null, true); //管理者全員へ送信
    push_send($send_info, $user_id); //特定ユーザーへ送信
*/

// プッシュ通知関数
if (! function_exists('push_send')) {
    //$send_info(title,body)
    function push_send($send_info, $user_id = null, $admin_flag = false){
        return PushNotification::push_send($send_info, $user_id, $admin_flag);
    }
}

function urlSafeBase64Decode($base64Url) {
    $base64 = strtr($base64Url, '-_', '+/');
    return base64_decode($base64);
}
class PushNotification
{
    public static function push_send($send_info, $user_id = null, $admin_flag = false)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "========================start========================");
        make_error_log($error_log, "user_id: ".$user_id. "  admin_flag: ".$admin_flag);

        //管理者充て(admin_flag=ture)の場合は複数名に送信するため、一旦配列にする
        $send_user_id_list=array();
        if($user_id){
            $send_user_id_list[0] = $user_id;
        }elseif($admin_flag){
            $user_list = User::getUserList(100,false,null,['search_admin_flag' => true]);
            foreach($user_list as $user){ $send_user_id_list[] = $user->id;}
        }else{
            make_error_log($error_log, "user_id and admin_flag are null");
            return;
        }

        $auth = [
            'VAPID' => [
                'subject' => trim(config('webpush.vapid.subject')), // 管理者のメールアドレスを設定ファイルから取得
                'publicKey' => trim(config('webpush.vapid.public_key')),
                'privateKey' => trim(config('webpush.vapid.private_key')),
            ],
        ];
        //make_error_log($error_log, "auth: " . print_r($auth['VAPID'],1));
        
        foreach($send_user_id_list as $id){
            $user_devices = UserDevice::getUserDevices($id);
            
            make_error_log($error_log, "user_id: ".$id);
            if (!$user_devices) {
                make_error_log($error_log, "user_devices is null");
                continue;
            }
            $subscription = Subscription::create([
                'endpoint' => $user_devices['endpoint'],
                'publicKey' => $user_devices['public_key'], 
                'authToken' => $user_devices['auth_token'], 
            ]);

        
            make_error_log($error_log, "subscription: " . print_r($subscription,1));

            
            $webPush = new WebPush($auth);
            try {
                $report = $webPush->sendOneNotification(
                    $subscription,
                    json_encode($send_info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                );
                // 送信成功の場合
                if ($report->isSuccess()) {
                    make_error_log($error_log, "Push notification sent successfully to user_id: " . $id);
                    UserLog::create_user_log($id,"push_send",true);
                } else {
                    $reason = $report->getReason();
                    make_error_log($error_log, "Push notification failed to send to user_id: " . $id);
                    make_error_log($error_log, "Reason: " . $reason);
                    UserLog::create_user_log($id,"push_send",false);
                    // 購読が失効・解除されている（410 Gone等）場合
                    if ($report->isSubscriptionExpired()) {
                        make_error_log($error_log, "Subscription expired or unsubscribed. Cleaning up database for user_id: " . $id);
                        
                        // デバイス情報を削除する処理を追加
                        UserDevice::delUserDevices($id);

                    }
                }

                    
                    
            } catch (\Exception $e) {
                // 例外の詳細をログに出力
                make_error_log($error_log, "Error Message: " . $e->getMessage());
                make_error_log($error_log, "Trace:". $e->getTraceAsString());

                UserLog::create_user_log($id,"push_send",false);
            }
            
        }
    }
}

