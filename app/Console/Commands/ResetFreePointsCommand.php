<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\CommonConfig;
use App\Services\PointService;

class ResetFreePointsCommand extends Command
{
    /**
     * Kernel.phpで指定したコマンド名
     */
    protected $signature = 'points:reset-free';

    /**
     * コマンドの説明
     */
    protected $description = '毎月1日に無償ポイントを指定数まで補充する';

    protected $pointService;

    // ★ PointServiceをコンストラクタで注入
    public function __construct(PointService $pointService)
    {
        parent::__construct();
        $this->pointService = $pointService;
    }

    /**
     * 実行される実際の処理
     */
    public function handle()
    {
        $conf_data = CommonConfig::getValues(['po_free','po_free_flag']);
        $free_point_reset_flag =  (bool)($conf_data['po_free_flag']->value1 ?? false);
        $service_free_point =  (int)($conf_data['po_free']->value2 ?? 0);
        
        $this->info('free_point_reset_flag: ' . ($free_point_reset_flag ? 'true' : 'false'));
        $this->info('service_free_point: ' . $service_free_point . 'pt.');

        if($free_point_reset_flag && $service_free_point > 0){
            $updateCount = 0;

            // ★ 懸念対策: メモリ枯渇を防ぐため、100件ずつチャンク処理で回す
            User::chunkById(100, function ($users) use ($service_free_point, &$updateCount) {
                foreach ($users as $user) {
                    // 現在の無償ポイント残高を取得
                    $currentFreePoint = $this->pointService->getBalance($user->id, 'free');

                    // 規定値に満たない場合、差分を補充する
                    if ($currentFreePoint < $service_free_point) {
                        $diff = $service_free_point - $currentFreePoint;

                        // 無償ポイント（is_pay = false）としてロット追加
                        $result = $this->pointService->add($user, $diff, false, "毎月1日の無償ポイント補充");
                        
                        if (!empty($result['success'])) {
                            $updateCount++;
                        }
                    }
                }
            });

            $this->info("Free points have been replenished for {$updateCount} users.");
            
            $send_info = new \stdClass();
            $send_info->title = "ポイント補充完了";
            $send_info->body = "毎月1日の無償ポイント補充処理が完了しました（対象: {$updateCount}名）";
            // ★ routes/web.php 等に合わせてルートを指定
            $send_info->url = route('admin.home'); // または任意の管理者URL
            
            push_send($send_info, null, false, true); //管理者全員へ送信
        }

        return Command::SUCCESS;
    }
}