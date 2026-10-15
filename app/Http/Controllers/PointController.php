<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // 追加
use Illuminate\Support\Str;         // 追加（注文番号生成用）

use App\Models\User;
use App\Models\CommonConfig;
use App\Models\PointOrder;          // 追加
use App\Services\PointService;
use Stripe\Stripe;                  // 追加
use Stripe\Checkout\Session;        // 追加

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
     * ポイント購入処理（Stripe Checkoutへのリダイレクト）
     */
    public function checkout(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // 選択されたパックIDを取得
        $configName = $request->input('config_name') ?? $request->input('pack_id');

        if (!$configName) {
            return redirect()->back()->with([
                'message' => 'パックが選択されていません。',
                'type'    => 'error',
                'sec'     => 3000
            ]);
        }

        // パック情報の取得
        $configs = CommonConfig::getValues([$configName]);
        $pack = $configs[$configName] ?? null;

        if (!$pack) {
            return redirect()->back()->with([
                'message' => '選択されたパック情報が見つかりません。',
                'type'    => 'error',
                'sec'     => 3000
            ]);
        }

        // 金額（value1）と付与ポイント（value2）を取得
        $amount = (int) ($pack->value1 ?? 0);
        $points = (int) ($pack->value2 ?? 0);
        $packName = $pack->description ?? 'ポイントパック';

        if ($amount <= 0 || $points <= 0) {
            return redirect()->back()->with([
                'message' => 'パックの設定が不正です。',
                'type'    => 'error',
                'sec'     => 3000
            ]);
        }

        try {
            // 1. 自社の注文番号（order_no）を生成 (例: ORD-20261015-1A2B3C)
            $orderNo = 'ORD-' . date('Ymd') . '-' . strtoupper(Str::random(6));

            // 2. point_orders テーブルに pending 状態で注文を作成
            $pointOrder = PointOrder::create([
                'order_no'            => $orderNo,
                'user_id'             => $user->id,
                'provider'            => 'stripe',
                'config_name'         => $configName,
                'amount'              => $amount,
                'points'              => $points,
                'valid_days'          => 180, // 有償ポイントの有効期限
                'status'              => 'pending',
            ]);

            // 1. CommonConfig から payment_test_mode の設定値を取得
            $confData = CommonConfig::getValues(['payment_test_mode']);
            $isTestModeFlag = (bool)($confData['payment_test_mode']->value1 ?? false);

            // 2. ログインユーザーが管理者、かつテストモードがONか判定
            // ※ is_admin のカラム名はご自身の環境（例: $user->role === 'admin' や $user->is_admin 等）に合わせて適宜変更してください
            $useTestMode = (bool)($user->admin_flag ?? false) && $isTestModeFlag;

            // 3. テストフラグに応じてキーを動的に取得
            $stripeSecret = $useTestMode 
                ? config('services.stripe.test.secret') 
                : config('services.stripe.live.secret');

            // 4. APIキーをセット
            Stripe::setApiKey($stripeSecret);

            // 4. Stripe Checkout Sessionの作成
            $checkout_session = Session::create([
                //'payment_method_types' => ['card'],   //パラメータの指定が廃止 され、Stripeダッシュボード側で自動管理する仕組みに変更された
                // ★ ここで自社の注文番号をStripeに持たせる（Webhookで突き合わせるため非常に重要）
                'client_reference_id'  => $orderNo,
                
                'line_items' => [[
                    'price_data' => [
                        'currency'     => 'jpy',
                        'product_data' => [
                            'name' => $packName,
                        ],
                        'unit_amount'  => $amount,
                    ],
                    'quantity'   => 1,
                ]],
                'mode' => 'payment',
                // 決済完了時・キャンセル時の戻り先URL
                'success_url' => route('point.success'),
                'cancel_url'  => route('point.cancel'),
            ]);

            // 5. 作成したセッションIDをDBに保存（必須ではないが、後で検索する際に便利）
            $pointOrder->update([
                'external_order_id' => $checkout_session->id,
            ]);

            // 6. Stripeの決済画面へリダイレクト
            return redirect($checkout_session->url);

        } catch (\Exception $e) {
            make_error_log($error_log, "Checkout Error: " . $e->getMessage());
            return redirect()->back()->with([
                'message' => '決済画面への遷移に失敗しました。時間をおいて再度お試しください。',
                'type'    => 'error',
                'sec'     => 3000
            ]);
        }
    }
    /**
     * 決済成功時の戻り先
     */
    public function success()
    {
        // 実際のポイント付与はWebhookで行うため、ここでは完了メッセージを出すだけ
        return redirect()->route('point.buy')->with([
            'message' => '決済手続きが完了しました。ポイント反映まで数分かかる場合があります。',
            'type'    => 'success',
            'sec'     => 5000
        ]);
    }

    /**
     * 決済キャンセル時の戻り先
     */
    public function cancel()
    {
        return redirect()->route('point.buy')->with([
            'message' => '決済がキャンセルされました。',
            'type'    => 'info',
            'sec'     => 3000
        ]);
    }
    /**
     * Stripe Webhook受信処理（決済完了通知の受け取りとポイント付与）
     */
    public function webhook(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        // Stripeから送られてきた署名とペイロード（生データ）
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        // ライブ環境 / テスト環境のシークレットをライブモード判定に応じて柔軟にフォールバック検証
        $data = json_decode($payload, true);
        $isLiveMode = $data['livemode'] ?? false;

        $secrets = $isLiveMode 
            ? [config('services.stripe.live.webhook_secret'), config('services.stripe.test.webhook_secret')]
            : [config('services.stripe.test.webhook_secret'), config('services.stripe.live.webhook_secret')];

        $event = null;
        $verified = false;

        try {
            foreach ($secrets as $secret) {
                if (empty($secret)) continue;
                try {
                    // Stripe SDKを利用して署名を検証し、安全なイベントオブジェクトを構築
                    $event = \Stripe\Webhook::constructEvent(
                        $payload, $sigHeader, $secret
                    );
                    $verified = true;
                    break;
                } catch (\Stripe\Exception\SignatureVerificationException $e) {
                    continue;
                }
            }

            if (!$verified) {
                make_error_log($error_log, "Invalid signature for all configured webhook secrets.");
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        } catch (\UnexpectedValueException $e) {
            make_error_log($error_log, "Invalid payload: " . $e->getMessage());
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $eventId = $event->id;

        // ★ すでに処理済みのイベントIDの場合は重複処理を避けるため即座に200を返す（べき等性の担保）
        if (PointOrder::where('stripe_event_id', $eventId)->exists()) {
            return response()->json(['status' => 'already_processed']);
        }

        // 決済完了イベント（checkout.session.completed）の処理
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderNo = $session->client_reference_id ?? null;
            $paymentIntentId = $session->payment_intent ?? null;

            if ($orderNo) {
                try {
                    // トランザクションと行ロックを使用して二重付与を完全に防止
                    \Illuminate\Support\Facades\DB::transaction(function () use ($orderNo, $paymentIntentId, $session, $eventId) {
                        // pending状態の注文のみをロックして取得
                        $order = PointOrder::where('order_no', $orderNo)
                            ->where('status', 'pending')
                            ->lockForUpdate()
                            ->first();

                        if ($order) {
                            // 1. 注文ステータスを paid に更新し、処理済みイベントIDとWebhookの生データを保存
                            $order->update([
                                'status'              => 'paid',
                                'external_payment_id' => $paymentIntentId,
                                'stripe_event_id'     => $eventId,
                                'metadata'            => $session->toArray(),
                            ]);

                            // 2. ユーザーにポイントを付与
                            $user = User::find($order->user_id);
                            if ($user) {
                                $this->pointService->add(
                                    $user,
                                    $order->points,
                                    true, // 有償ポイント
                                    "Stripe決済 (注文番号: {$order->order_no})",
                                    $order->id // point_order_id
                                );
                            }
                        }
                    });
                } catch (\Exception $e) {
                    make_error_log($error_log, "Webhook DB Transaction Error: " . $e->getMessage());
                    return response()->json(['error' => 'Internal server error'], 500);
                }
            }
        }
        // 決済失敗イベントのハンドリング（運用監視用ログ出力）
        elseif ($event->type === 'payment_intent.payment_failed') {
            $paymentIntent = $event->data->object;
            make_error_log($error_log, "Payment failed for intent: " . ($paymentIntent->id ?? 'unknown'));
        }

        // Stripeサーバーへ正常受信(HTTP 200)を返す
        return response()->json(['status' => 'success']);
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