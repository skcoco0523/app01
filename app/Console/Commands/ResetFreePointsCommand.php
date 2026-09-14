<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CommonConfig;

class ResetFreePointsCommand extends Command
{
    /**
     * Kernel.phpで指定したコマンド名
     */
    protected $signature = 'points:reset-free';

    /**
     * コマンドの説明
     */
    protected $description = '毎月1日に全ユーザーの無料ポイントを90ptへリセット';

    /**
     * 実行される実際の処理
     */
    public function handle()
    {
        
        $conf_data = CommonConfig::getValues(['po_free','po_free_flag']);
        $free_point_reset_flag =  (bool)$conf_data['po_free_flag']->value1;
        $service_free_point =  $conf_data['po_free']->value2;
        
        $this->info('free_point_reset_flag: ' . $free_point_reset_flag);
        $this->info('service_free_point: ' . $service_free_point . 'pt.');

        if($free_point_reset_flag){
            // free_point が $service_free_point 未満のユーザーのみ 90pt に引き上げ更新
            User::where('free_point', '<', $service_free_point)
            ->update(['free_point' => $service_free_point]);

            $this->info('Free points have been reset to ' . $service_free_point . 'pt.');
            
            $send_info = new \stdClass();
            $send_info->title = "ポイントリセット";
            $send_info->body = "1日になりポイントがリセットされました";
            $send_info->url = route('profile.show');
            
            push_send($send_info, null, false, true); //管理者全員へ送信
        }

        return Command::SUCCESS;
    }
}