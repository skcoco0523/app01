<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\CommonConfig;

class PointController extends Controller
{
    /**
     * ポイント購入ページ表示
     */
    public function buy(Request $request)
    {
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $profile = User::getProfile($user->id);
        $common_conf_names = [
            'po_pack1', 'po_pack2', 'po_pack3', 'po_pack4', 'po_pack5', 'po_pack6'
        ];
        $packs = CommonConfig::getValues($common_conf_names);

        // 午前(AM) / 午後(PM) 判定
        $isAm = date('H') < 12;
        $periodLabel = $isAm ? 'AM' : 'PM';
        
        // 1区分（午前/午後）あたりの上限
        $maxDailyLimit = 5;
        $cacheKey = 'ad_watch_count_' . $user->id . '_' . date('Y-m-d') . '_' . $periodLabel;
        $watchedCount = (int) Cache::get($cacheKey, 0);
        $remainingAdCount = max(0, $maxDailyLimit - $watchedCount);

        $msg = "";
            
        return view('point.buy', compact('profile', 'packs', 'msg', 'remainingAdCount', 'maxDailyLimit', 'periodLabel'));
    }

    /**
     * ポイント購入処理（決済）
     */
    public function checkout(Request $request)
    {
        $packId = $request->input('pack_id');
        return back()->with('message', '選択されたパック: ' . $packId);
    }

    /**
     * 広告視聴・無料ポイント獲得処理（午前/午後 各5回制限）
     */
    public function ad(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => '未ログインです'], 401);
        }

        // 午前(AM) / 午後(PM) 判定
        $isAm = date('H') < 12;
        $periodLabel = $isAm ? 'AM' : 'PM';
        $periodText = $isAm ? '午前' : '午後';
        $nextTimeMsg = $isAm ? '12:00以降に再度お試しください。' : 'また明日お試しください。';

        $maxDailyLimit = 5; 
        $cacheKey = 'ad_watch_count_' . $user->id . '_' . date('Y-m-d') . '_' . $periodLabel;
        $watchedCount = (int) Cache::get($cacheKey, 0);

        // 上限チェック
        if ($watchedCount >= $maxDailyLimit) {
            return response()->json([
                'success' => false,
                'message' => "{$periodText}の広告視聴上限（{$maxDailyLimit}回）に達しました。{$nextTimeMsg}"
            ], 400);
        }

        // ポイント付与実行（10pt無償ポイント）
        $result = $user->add_po(10, false, '広告視聴');

        if (!empty($result['success'])) {
            // キャッシュ期限設定（午前なら12:00まで、午後なら23:59:59まで）
            $expiresAt = $isAm ? now()->setTime(12, 0, 0) : now()->endOfDay();
            Cache::put($cacheKey, $watchedCount + 1, $expiresAt);
        }

        return response()->json([
            'success'     => $result['success'],
            'total_point' => $result['total_point'] ?? 0,
            'message'     => $result['message'] ?? '',
            'remaining'   => $maxDailyLimit - ($watchedCount + 1)
        ]);
    }
}