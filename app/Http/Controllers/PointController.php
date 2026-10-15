<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\CommonConfig;
use App\Services\PointService;

class PointController extends Controller
{
    protected $pointService;

    public function __construct(PointService $pointService)
    {
        $this->pointService = $pointService;
    }

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

        // ★ PointServiceから最新の残高を取得してBladeに渡す
        $pointData = $this->pointService->check($user, 0);

        $common_conf_names = [
            'po_pack1', 'po_pack2', 'po_pack3', 'po_pack4', 'po_pack5', 'po_pack6'
        ];
        $packs = CommonConfig::getValues($common_conf_names);

        $conf_data = CommonConfig::getValues(['po_pack_late','po_ad_reward','po_ad_reward','po_whisper']);
        // 1円あたりの標準付与ポイント数（例: 1円＝1ptなら 1）
        $po_pack_late = $conf_data['po_pack_late']->value1;
        $po_ad_reward = $conf_data['po_ad_reward']->value1;
        
        // Whisper（文字起こし）におけるポイント、無料枠
        $whisper_po =  $conf_data['po_whisper']->value1;
        $whisper_free =  $conf_data['po_whisper']->value2;
        
        foreach ($packs as $key => $pack) {
            // （獲得ポイント） - （価格 × 標準レート） ＝ お得なポイント数
            $pack->bonus = $pack->value2 - ($pack->value1 * $po_pack_late);
        }
        
        // 午前(AM) / 午後(PM) 判定
        $isAm = date('H') < 12;
        $periodLabel = $isAm ? 'AM' : 'PM';
        
        // 1区分（午前/午後）あたりの上限
        $maxDailyLimit = 5;
        $cacheKey = 'ad_watch_count_' . $user->id . '_' . date('Y-m-d') . '_' . $periodLabel;
        $watchedCount = (int) Cache::get($cacheKey, 0);
        $remainingAdCount = max(0, $maxDailyLimit - $watchedCount);

        $msg = "";
            
        return view('point.buy', compact('profile', 'packs', 'po_ad_reward', 'whisper_po', 'whisper_free', 'remainingAdCount', 'maxDailyLimit', 'periodLabel', 'msg', 'pointData'));
    }

    /**
     * ポイント説明ページ表示
     */
    public function about(Request $request)
    {
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $profile = User::getProfile($user->id);
        
        // ★ 最新の残高を取得してBladeに渡す
        $pointData = $this->pointService->check($user, 0);

        $conf_data = CommonConfig::getValues(['po_free','po_free_flag','po_ad_reward','po_whisper']);
        // 1日あたりの無料ポイント数
        $service_free_point =  $conf_data['po_free']->value1;
        $free_point_reset_flag =  (bool)$conf_data['po_free_flag']->value1;
        // 広告視聴による付与ポイント数
        $ad_reward_point =  $conf_data['po_ad_reward']->value1;
        // Whisper（文字起こし）におけるポイント、無料枠
        $whisper_po =  $conf_data['po_whisper']->value1;
        $whisper_free =  $conf_data['po_whisper']->value2;

        $msg = "";
            
        return view('point.about', compact('profile', 'service_free_point', 'free_point_reset_flag', 'ad_reward_point', 'whisper_po', 'whisper_free', 'msg', 'pointData'));
    }

    /**
     * ポイント購入処理（決済）
     */
    public function checkout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // buy.blade.php のラジオボタン (name="config_name") から取得
        $configName = $request->input('config_name') ?? $request->input('pack_id');

        if (!$configName) {
            return redirect()->back()->with([
                'message' => 'パックが選択されていません。',
                'type'    => 'error',
                'sec'     => 3000
            ]);
        }

        // ★ 管理者ユーザーの場合：即時テスト付与を実行
        if ($user->admin_flag) {
            $configs = CommonConfig::getValues([$configName]);
            $pack = $configs[$configName] ?? null;

            if (!$pack) {
                return redirect()->back()->with([
                    'message' => '選択されたパック情報が見つかりません。',
                    'type'    => 'error',
                    'sec'     => 3000
                ]);
            }

            $points = (int) ($pack->value2 ?? 0);
            $packName = $pack->description ?? $configName;

            if ($points <= 0) {
                return redirect()->back()->with([
                    'message' => 'パックの付与ポイント設定が不正です。',
                    'type'    => 'error',
                    'sec'     => 3000
                ]);
            }

            // 有償ポイントとしてロット台帳へ付与（PointServiceを使用）
            $result = $this->pointService->add($user, $points, true, "管理者テスト購入 ({$packName})");

            if (!empty($result['success'])) {
                return redirect()->route('point.buy')->with([
                    'message' => "【管理者テスト】{$points}pt（有償）を付与しました。",
                    'type'    => 'success',
                    'sec'     => 3000
                ]);
            } else {
                return redirect()->back()->with([
                    'message' => 'ポイント付与に失敗しました: ' . ($result['message'] ?? ''),
                    'type'    => 'error',
                    'sec'     => 3000
                ]);
            }
        }

        // 一般ユーザー向け（決済代行実装前の仮処理）
        return redirect()->back()->with([
            'message' => '現在、決済機能は準備中です。（管理者アカウントのみ即時付与テストが可能です）',
            'type'    => 'warning',
            'sec'     => 3000
        ]);
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

        // ★ ポイント付与実行（PointServiceを利用）
        $conf_data = CommonConfig::getValues(['po_ad_reward']);
        $ad_reward_point = (int) ($conf_data['po_ad_reward']->value1 ?? 0);
        
        $result = $this->pointService->add($user, $ad_reward_point, false, '広告視聴');

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