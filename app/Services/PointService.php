<?php

namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Models\UserPoint;
use App\Models\UserLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PointService
{
    // ★ 1リクエスト内のキャッシュを保持するプロパティ
    protected $cache = [];

    /**
     * キャッシュキーの生成
     */
    protected function getCacheKey(int $userId): string
    {
        return 'user_' . $userId;
    }

    /**
     * キャッシュのクリア（ポイント変動時に呼び出す）
     */
    public function clearCache(int $userId): void
    {
        $key = $this->getCacheKey($userId);
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
        }
    }

    /**
     * ポイント残高の取得（内部用・表示用）
     */
    public function getBalance(int $userId, string $type): int
    {
        return (int) UserPoint::where('user_id', $userId)
            ->where('type', $type)
            ->where('point_rem', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
            })
            ->sum('point_rem');
    }

    /**
     * ポイント残高 ＆ 利用可否チェック
     * （Web時は1リクエスト内キャッシュ、CLI実行時は常にDB最新値を参照）
     */
    public function check(User $user, int $req_points = 1, bool $is_pay_only = false): array
    {
        $isConsole = app()->runningInConsole();
        $cacheKey  = $this->getCacheKey($user->id);

        // CLI環境、またはキャッシュ未保持の場合にDBから集計
        if ($isConsole || !isset($this->cache[$cacheKey])) {
            $free = $this->getBalance($user->id, 'free');
            $pay  = $this->getBalance($user->id, 'paid');
            
            $pointData = [
                'free_point'  => $free,
                'pay_point'   => $pay,
                'total_point' => $free + $pay,
            ];

            // Webリクエスト時のみインメモリキャッシュに保存
            if (!$isConsole) {
                $this->cache[$cacheKey] = $pointData;
            }
        } else {
            $pointData = $this->cache[$cacheKey];
        }

        $total   = $pointData['total_point'];
        $pay     = $pointData['pay_point'];
        $can_use = $is_pay_only ? ($pay >= $req_points) : ($total >= $req_points);

        return array_merge($pointData, [
            'can_use' => $can_use,
        ]);
    }

    /**
     * ポイント付与処理
     */
    public function add($user, int $points, bool $isPay = false, string $memo = '', ?int $pointOrderId = null)
    {
        // ★ プロジェクト規約に従ったログファイル名の定義
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        if ($points <= 0) {
            return ['success' => false, 'message' => '追加するポイントは1以上を指定してください。'];
        }

        try {
            DB::transaction(function () use ($user, $points, $isPay, $pointOrderId) {
                // 有効期限の計算 (有償: 180日, 無償: 期限なし)
                $expiredAt = $isPay ? Carbon::now()->addDays(180) : null;

                // user_point テーブルへロットをインサート
                DB::table('user_point')->insert([
                    'user_id'        => $user->id,
                    'point_order_id' => $pointOrderId,
                    'type'           => $isPay ? 'paid' : 'free',
                    'point'          => $points,
                    'point_rem'      => $points,
                    'expired_at'     => $expiredAt,
                    'created_at'     => Carbon::now(),
                    'updated_at'     => Carbon::now(),
                ]);
            });

            // キャッシュをクリア
            $this->clearCache($user->id);

            return ['success' => true];
        } catch (Exception $e) {
            // ★ プロジェクト共通のログヘルパーに統一
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['success' => false, 'message' => 'ポイントの付与に失敗しました。'];
        }
    }
    /**
     * ポイント消費処理
     */
    public function consume(User $user, int $use_points = 1, bool $is_pay_only = false, string $memo = null): array
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        if ($use_points <= 0) {
            return ['success' => false, 'error_code' => 'INVALID_POINTS', 'message' => '消費ポイントが不正です'];
        }

        try {
            return DB::transaction(function () use ($user, $use_points, $is_pay_only, $memo) {
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                // 事前チェック
                $check = $this->check($lockedUser, $use_points, $is_pay_only);
                if (!$check['can_use']) {
                    $type = $is_pay_only ? 'PAY_POINT_SHORTAGE' : 'POINT_SHORTAGE';
                    $msg  = $is_pay_only ? '有償ポイントが不足しています' : 'ポイントが不足しています';
                    
                    UserLog::create_user_log($lockedUser->id, 'point_consume_failed', false, "ポイント不足 (必要:{$use_points}) [{$memo}]");
                    return ['success' => false, 'error_code' => $type, 'message' => $msg];
                }

                // 消費確定に伴いキャッシュをクリア
                $this->clearCache($lockedUser->id);

                $remainingToDeduct = $use_points;

                $query = UserPoint::where('user_id', $lockedUser->id)
                    ->where('point_rem', '>', 0)
                    ->where(function ($q) {
                        $q->whereNull('expired_at')->orWhere('expired_at', '>', now());
                    });

                if ($is_pay_only) {
                    $query->where('type', 'paid')->orderBy('expired_at', 'asc');
                } else {
                    $query->orderByRaw("CASE WHEN type = 'free' THEN 0 ELSE 1 END")
                          ->orderBy('expired_at', 'asc');
                }

                $lots = $query->get();

                foreach ($lots as $lot) {
                    if ($remainingToDeduct <= 0) break;

                    $deduct = min($lot->point_rem, $remainingToDeduct);
                    $lot->point_rem -= $deduct;
                    $lot->save();

                    $remainingToDeduct -= $deduct;
                }

                if ($remainingToDeduct > 0) {
                    throw new Exception('ポイント消費処理中に残高不足エラーが発生しました。');
                }

                if ($is_pay_only) {
                    $freshFree = $check['free_point'];
                    $freshPay  = $check['pay_point'] - $use_points;
                    $logType   = 'point_consume_pay';
                } else {
                    if ($check['free_point'] >= $use_points) {
                        $freshFree = $check['free_point'] - $use_points;
                        $freshPay  = $check['pay_point'];
                        $logType   = 'point_consume_free';
                    } else {
                        $deficit   = $use_points - $check['free_point'];
                        $freshFree = 0;
                        $freshPay  = $check['pay_point'] - $deficit;
                        $logType   = 'point_consume_mixed';
                    }
                }
                
                UserLog::create_user_log($lockedUser->id, $logType, true, "pt消費(-{$use_points}pt) 残:free={$freshFree},pay={$freshPay} [{$memo}]");

                return [
                    'success'     => true,
                    'free_point'  => $freshFree,
                    'pay_point'   => $freshPay,
                    'total_point' => $freshFree + $freshPay,
                ];
            });
        } catch (Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return ['success' => false, 'error_code' => 'SYSTEM_ERROR', 'message' => 'ポイント消費処理中にエラーが発生しました'];
        }
    }
}