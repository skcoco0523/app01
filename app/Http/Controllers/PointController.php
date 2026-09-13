<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\CommonConfig;


class PointController extends Controller
{
    //ポイント購入ページ
    public function buy(Request $request)
    {
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        $profile = User::getProfile(Auth::id());
        $common_conf_names = [
            'po_pack1', 'po_pack2', 'po_pack3', 'po_pack4', 'po_pack5', 'po_pack6'
        ];
        $packs = CommonConfig::getValues($common_conf_names);
        //dd($packs);

        $msg = "";
            
        return view('point.buy', compact('profile','packs', 'msg'));

    }
    //ポイント購入処理
    public function checkout(Request $request)
    {
        // 選択されたパックIDの受け取り（例: p500, p1000など）
        $packId = $request->input('pack_id');

        // TODO: Stripe等の決済処理・DB更新ロジックをここに実装
        
        return back()->with('message', '選択されたパック: ' . $packId);
    }
    /**
     * 広告視聴・無料ポイント獲得処理
     */
    public function ad(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json(['success' => false, 'message' => '未ログインです'], 401);
        }

        // 1日あたりの上限設定
        $maxDailyLimit = 5; 
        
        // ユーザーIDと日付を組み合わせたキャッシュキー
        $cacheKey = 'ad_watch_count_' . $user->id . '_' . date('Y-m-d');
        $watchedCount = (int) Cache::get($cacheKey, 0);

        // 視聴回数上限チェック
        if ($watchedCount >= $maxDailyLimit) {
            return response()->json([
                'success' => false,
                'message' => "本日の広告視聴上限（{$maxDailyLimit}回）に達しました。また明日お試しください。"
            ], 400);
        }

        // ポイント付与実行（10pt無償ポイント）
        $result = $user->add_po(10, false, '広告視聴');

        if (!empty($result['success'])) {
            // 視聴完了時にカウントを+1し、本日の23:59:59まで保持
            Cache::put($cacheKey, $watchedCount + 1, now()->endOfDay());
        }

        return response()->json([
            'success'     => $result['success'],
            'total_point' => $result['total_point'] ?? 0,
            'message'     => $result['message'] ?? '',
            'remaining'   => $maxDailyLimit - ($watchedCount + 1) // 残り回数
        ]);
    }

}
