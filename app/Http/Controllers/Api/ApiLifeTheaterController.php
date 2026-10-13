<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Controllers\LifeTheaterController;

use App\Models\LifeTheater;
use App\Models\LifeTheaterShare;
use App\Models\LifeTheaterSlide;
use App\Models\LifeTheaterSlideObject;
use App\Models\LifeTheaterPublicLink;
use App\Models\CommonConfig;
use App\Models\UserExtraSlot;

class ApiLifeTheaterController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * ライフシアターの共有権限・共有状態の管理API
     */
    public function api_life_theater_manage(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");

        $input = $request->all();
        $type  = $request->route('type');
        make_error_log($error_log, "type:" . $type . " input:" . print_r($input, true));

        // 1. 所有者チェック（操作対象の作品がログインユーザーの所有物か確認）
        $input['search_life_theater_id'] = get_proc_data($input, "life_theater_id");
        $theater                         = LifeTheater::getLifeTheaterList(null, false, null, $input)->first();
        if (!$theater) {
            make_error_log($error_log, "Theater not found or access denied. life_theater_id:" . $input['search_life_theater_id']);
            return false;
        }

        // 2. 共有状態の確認
        $life_theater_id = get_proc_data($input, "life_theater_id");
        $friend_id       = get_proc_data($input, "friend_id");

        $sharing_theater = LifeTheaterShare::where('life_theater_id', $life_theater_id)
            ->where('user_id', $friend_id)
            ->first();

        // 操作タイプ別の事前ガードチェック
        if ($type == 'share' && $sharing_theater) {
            make_error_log($error_log, "Already shared. friend_id:" . $friend_id);
            return false;
        }
        if ($type == 'unshare' && !$sharing_theater) {
            make_error_log($error_log, "Not shared yet. friend_id:" . $friend_id);
            return false;
        }
        if ($type == 'enable_edit' && !$sharing_theater) {
            make_error_log($error_log, "Not shared item. Cannot enable edit. friend_id:" . $friend_id);
            return false;
        }
        if ($type == 'disable_edit' && !$sharing_theater) {
            make_error_log($error_log, "Not shared item. Cannot disable edit. friend_id:" . $friend_id);
            return false;
        }

        $input['user_id']         = $friend_id;
        $input['life_theater_id'] = $life_theater_id;

        // 3. 各操作の実行
        if ($type == 'share') {
            $ret = LifeTheaterShare::createShareLifeTheater($input);
            if ($ret['error_code'] == 0) {
                make_error_log($error_log, "Shared successfully.");

                // 共有成功時は相手ユーザーにプッシュ通知を送信
                $send_info        = new \stdClass();
                $send_info->title = "ライフシアターが共有されました";
                $send_info->body  = "共有者：" . Auth::user()->name . "\n作品: " . $theater->title;
                $send_info->url   = route('life_theater.show', ['id' => $theater->id, 'share_flag' => 1]);

                push_send($send_info, $friend_id);
            } else {
                make_error_log($error_log, "Failed to share. error_code=" . $ret['error_code']);
            }
        } elseif ($type == 'unshare') {
            $input['id'] = $sharing_theater->id;
            $ret = LifeTheaterShare::delShareLifeTheater($input);
            make_error_log($error_log, "Unshare executed. error_code=" . ($ret['error_code'] ?? 0));
        } elseif ($type == 'enable_edit') {
            LifeTheaterShare::where('id', $sharing_theater->id)->update(['admin_flag' => true]);
            make_error_log($error_log, "Enabled edit permission. share_id:" . $sharing_theater->id);
        } elseif ($type == 'disable_edit') {
            LifeTheaterShare::where('id', $sharing_theater->id)->update(['admin_flag' => false]);
            make_error_log($error_log, "Disabled edit permission. share_id:" . $sharing_theater->id);
        }

        return true;
    }

    /**
     * スライドの保存・更新 (Ajax API)
     */
    public function updateSlide(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $input = $request->all();
            make_error_log($error_log, "input:" . print_r($input, true));

            $slide = LifeTheaterSlide::with('lifeTheater')->find($input['id'] ?? null);
            if (!$slide) {
                make_error_log($error_log, "Validation error: slide not found. id:" . ($input['id'] ?? 'null'));
                return response()->json(['status' => 'error', 'message' => '対象のスライドが存在しません。'], 400);
            }

            $isPremium = ($slide->lifeTheater->plan_type ?? '') === 'premium';

            $currentConfig        = LifeTheaterSlide::parseConfig($slide->config_data ?? null);
            $inputConfig          = $input['config_data'] ?? [];
            $input['config_data'] = LifeTheaterSlide::filterConfigByPlan($inputConfig, $isPremium, $currentConfig);

            $ret = LifeTheaterSlide::chgSlide($input);

            if ($ret['error_code'] == 0) {
                make_error_log($error_log, "Success saved slide_id:" . $input['id']);

                // 再生キャッシュクリア
                LifeTheaterController::clearPlayCache($slide->life_theater_id);

                // 最新のスライド情報とメディア情報を取得して返却
                $updatedSlide = LifeTheaterSlide::with('media')->find($input['id']);
                if ($updatedSlide) {
                    $updatedSlide->parsed_config = LifeTheaterSlide::parseConfig($updatedSlide->config_data);
                }

                return response()->json([
                    'status'  => 'success',
                    'message' => 'スライドを更新しました。',
                    'data'    => $updatedSlide
                ]);
            }

            make_error_log($error_log, "Failed to save slide. error_code=" . $ret['error_code']);
            return response()->json(['status' => 'error', 'message' => 'スライドの更新に失敗しました。'], 400);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '更新中にエラーが発生しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * スライドオブジェクト一覧の取得 (parsed_config を付与)
     */
    public function getSlideObjects(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $slide_id = $request->input('slide_id');
            make_error_log($error_log, "slide_id:" . $slide_id);

            if (!$slide_id) {
                make_error_log($error_log, "Validation error: slide_id is missing.");
                return response()->json(['status' => 'error', 'message' => 'スライドIDが必要です。'], 400);
            }

            $objects = LifeTheaterSlideObject::where('life_theater_slide_id', $slide_id)
                ->with('media')
                ->orderBy('sort_order', 'asc')
                ->get();

            // パース済み設定オブジェクトをアタッチ
            foreach ($objects as $obj) {
                $obj->parsed_config = LifeTheaterSlideObject::parseConfig($obj->config_data);
            }

            make_error_log($error_log, "Success. Objects count: " . count($objects));
            return response()->json(['status' => 'success', 'data' => $objects]);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '取得に失敗しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * スライドオブジェクトの保存（作成 / 更新）
     */
    public function saveSlideObject(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $input = $request->all();
            
            $slideId = $input['life_theater_slide_id'] ?? null;
            if (!$slideId && !empty($input['id'])) {
                $targetObj = LifeTheaterSlideObject::find($input['id']);
                $slideId   = $targetObj->life_theater_slide_id ?? null;
            }

            $slide     = LifeTheaterSlide::with('lifeTheater')->find($slideId);
            $isPremium = ($slide->lifeTheater->plan_type ?? '') === 'premium';
            // 新規作成時のみ、オブジェクト登録上限をチェック
            if (empty($input['id']) && $slide && $slide->lifeTheater) {
                $currentObjCount = LifeTheaterSlideObject::where('life_theater_slide_id', $slideId)->count();
                $maxObjLimit     = $slide->lifeTheater->getObjectLimit();

                if ($currentObjCount >= $maxObjLimit) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => "このスライドに配置できる上限（{$maxObjLimit}個）に達しています。"
                    ], 400);
                }
            }

            // 新規作成時のみ、オブジェクト（1スライドあたり）の登録上限をチェック
            if (empty($input['id'])) {
                $currentObjCount = LifeTheaterSlideObject::where('life_theater_slide_id', $slideId)->count();
                $maxObjLimit     = $slide->lifeTheater->getObjectLimit();
                
                if ($currentObjCount >= $maxObjLimit) {
                    return response()->json([
                        'status'  => 'error', 
                        'message' => "このスライドに配置できるオブジェクトの上限（{$maxObjLimit}個）に達しています。"
                    ], 400);
                }
            }

            $currentObj    = !empty($input['id']) ? LifeTheaterSlideObject::find($input['id']) : null;
            $currentConfig = $currentObj ? LifeTheaterSlideObject::parseConfig($currentObj->config_data) : [];
            $inputConfig   = $input['config_data'] ?? [];

            $input['config_data'] = LifeTheaterSlideObject::filterConfigByPlan($inputConfig, $isPremium, $currentConfig);

            if (!empty($input['id'])) {
                make_error_log($error_log, "Action: update object_id:" . $input['id']);
                $ret = LifeTheaterSlideObject::chgObject($input);
            } else {
                make_error_log($error_log, "Action: create slide_id:" . ($input['life_theater_slide_id'] ?? 'null'));
                $ret = LifeTheaterSlideObject::createObject($input);
            }

            if ($ret['error_code'] == 0) {
                make_error_log($error_log, "Success saved object.");

                // 再生キャッシュクリア
                if ($slide && $slide->life_theater_id) {
                    LifeTheaterController::clearPlayCache($slide->life_theater_id);
                }

                return response()->json([
                    'status'  => 'success', 
                    'message' => 'オブジェクトを保存しました。'
                ]);
            }

            make_error_log($error_log, "Failed to save object. error_code=" . $ret['error_code']);
            return response()->json([
                'status'  => 'error', 
                'message' => '保存に失敗しました。'
            ], 400);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '保存中にエラーが発生しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * スライドオブジェクトの削除
     */
    public function destroySlideObject(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $input = $request->all();
            make_error_log($error_log, "input:" . print_r($input, true));

            if (empty($input['id'])) {
                make_error_log($error_log, "Validation error: object id is missing.");
                return response()->json(['status' => 'error', 'message' => 'オブジェクトIDが必要です。'], 400);
            }

            $obj = LifeTheaterSlideObject::with('slide')->find($input['id']);
            $theaterId = $obj->slide->life_theater_id ?? null;

            $ret = LifeTheaterSlideObject::delObject($input);

            if ($ret['error_code'] == 0) {
                make_error_log($error_log, "Success deleted object_id:" . $input['id']);

                // 再生キャッシュクリア
                if ($theaterId) {
                    LifeTheaterController::clearPlayCache($theaterId);
                }

                return response()->json(['status' => 'success', 'message' => 'オブジェクトを削除しました。']);
            }

            make_error_log($error_log, "Failed to delete object. error_code=" . $ret['error_code']);
            return response()->json(['status' => 'error', 'message' => '削除に失敗しました。'], 400);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '削除中にエラーが発生しました: ' . $e->getMessage()], 500);
        }
    }
    
    //==============================================================================
    // ここから公開関連API処理 (public_link.list, public_link.store, public_link.destroy)
    //==============================================================================

    /**
     * 発行済み公開URL一覧の取得 (Ajax)
     */
    public function api_getPublicLinks(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $lifeTheaterId = $request->input('life_theater_id');
            $user = Auth::user();

            if (!$lifeTheaterId) {
                return response()->json(['status' => 'error', 'message' => '作品IDが必要です。'], 400);
            }

            $links = LifeTheaterPublicLink::where('life_theater_id', $lifeTheaterId)
                ->where('user_id', $user->id)
                ->latest()
                ->get();

            // 各リンクに完全な公開URL文字列をアタッチして返却
            foreach ($links as $link) {
                $link->public_url = route('life_theater.public_play', ['token' => $link->token]);
            }

            $theater = LifeTheater::find($lifeTheaterId);
            $isPremium = ($theater->plan_type ?? '') === 'premium';
            
            $usedAdFreeCount = LifeTheaterPublicLink::where('life_theater_id', $lifeTheaterId)
                ->where('is_ad_free', true)
                ->count();

            $common_conf_names = ['po_life_theater_url'];
            $configs = CommonConfig::getValues($common_conf_names);
            $po_cost    = isset($configs['po_life_theater_url']) ? (int)$configs['po_life_theater_url']->value1 : 300;
            $free_limit = isset($configs['po_life_theater_url']) ? (int)$configs['po_life_theater_url']->value2 : 1;

            return response()->json([
                'status' => 'success',
                'data'   => $links,
                'meta'   => [
                    'is_premium'         => $isPremium,
                    'used_ad_free_count' => $usedAdFreeCount,
                    'free_limit'         => $free_limit,
                    'url_cost'           => $po_cost,
                    'has_free_ticket'    => $isPremium && ($usedAdFreeCount < $free_limit)
                ]
            ]);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '取得に失敗しました。'], 500);
        }
    }

    /**
     * 公開URLの発行 (Ajax - DBトランザクション保護付き)
     */
    public function api_storePublicLink(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $input = $request->all();
            $user = Auth::user();
            $theaterId = $input['life_theater_id'] ?? null;
            $requestAdFree = filter_var($input['request_ad_free'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$theaterId) {
                return response()->json(['status' => 'error', 'message' => '対象の作品が指定されていません。'], 400);
            }

            $theater = LifeTheater::find($theaterId);
            if (!$theater) {
                return response()->json(['status' => 'error', 'message' => '作品が存在しないか権限がありません。'], 403);
            }

            // トランザクション処理により、ポイント引き落としとURL生成の不整合を防止
            return DB::transaction(function () use ($theater, $user, $requestAdFree, $input, $error_log) {

                // 1作品あたりの発行上限チェック（最大10件まで）
                $totalLinkCount = LifeTheaterPublicLink::where('life_theater_id', $theater->id)->count();
                if ($totalLinkCount >= 10) {
                    return response()->json(['status' => 'error', 'message' => '1つの作品で発行できる公開URLは最大10件までです。不要なURLを削除してから再度お試しください。'], 400);
                }

                $usedAdFreeCount = LifeTheaterPublicLink::where('life_theater_id', $theater->id)
                    ->where('is_ad_free', true)
                    ->count();

                // 広告なしフラグの初期化
                $isAdFree = false;

                if ($requestAdFree) {
                    $common_conf_names = ['po_life_theater_url'];
                    $configs = CommonConfig::getValues($common_conf_names);

                    $po_cost    = isset($configs['po_life_theater_url']) ? (int)$configs['po_life_theater_url']->value1 : 300;
                    $free_limit = isset($configs['po_life_theater_url']) ? (int)$configs['po_life_theater_url']->value2 : 1;

                    if (($theater->plan_type ?? '') === 'premium' && $usedAdFreeCount < $free_limit) {
                        // Premium特典（無料枠件数以内なら無料）
                        $isAdFree = true;
                    } else {
                        // ポイントチェック
                        $check = $user->po_check($po_cost);
                        if (!$check['can_use']) {
                            return response()->json([
                                'status'  => 'error', 
                                'message' => 'ポイントが不足しています。（必要: ' . $po_cost . 'pt）'
                            ], 400);
                        }

                        // ポイント消費
                        $poResult = $user->po_use($po_cost, false, "ライフシアター 広告なし公開URL発行");
                        if (!$poResult['success']) {
                            throw new \Exception('ポイント処理に失敗しました。');
                        }

                        $isAdFree = true;
                    }
                }

                $token = LifeTheaterPublicLink::generateUniqueToken();

                // 全角スペースも除去して保存
                $password = null;
                if (!empty($input['password'])) {
                    // 全角・半角スペースを除去
                    $password = preg_replace('/[\p{Z}\p{Cc}]/u', '', $input['password']);
                }

                LifeTheaterPublicLink::create([
                    'life_theater_id' => $theater->id,
                    'user_id'         => $user->id,
                    'token'           => $token,
                    'memo'            => $input['memo'] ?? null,
                    'is_ad_free'      => $isAdFree,
                    'password'        => $password, // ★ 平文保存
                    'is_active'       => true,
                ]);

                return response()->json([
                    'status'      => 'success',
                    'message'     => '公開URLを発行しました。',
                    'total_point' => $user->fresh()->total_point ?? null
                ]);
            });

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '発行処理中にエラーが発生しました: ' . $e->getMessage()], 500);
        }
    }

    /**
     * 公開URLの削除 (Ajax)
     */
    public function api_destroyPublicLink(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $input = $request->all();
            $linkId = $input['link_id'] ?? null;
            $user = Auth::user();

            make_error_log($error_log, "link_id:" . $linkId . " user_id:" . ($user->id ?? 'null'));

            if (!$linkId) {
                return response()->json(['status' => 'error', 'message' => 'リンクIDが送信されていません。'], 400);
            }

            $link = LifeTheaterPublicLink::find($linkId);

            if (!$link || (int)$link->user_id !== (int)$user->id) {
                make_error_log($error_log, "Permission error or link not found. link_id:" . $linkId);
                return response()->json(['status' => 'error', 'message' => '権限がないか、データが存在しません。'], 403);
            }

            $link->delete();

            make_error_log($error_log, "Successfully deleted link_id:" . $linkId);

            return response()->json([
                'status'  => 'success',
                'message' => '公開URLを削除しました。'
            ]);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '削除中にエラーが発生しました: ' . $e->getMessage()], 500);
        }
    }


    
    //==============================================================================
    // ここから課金による拡張
    //==============================================================================
    /**
     * ライフシアターの作品作成枠を拡張する (Ajax - DBトランザクション保護付き)
     */
    public function expandTheaterLimit(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $user = Auth::user();

            // ★共通設定から消費ポイントと追加枠数を取得（設定がない場合は 300pt で +1枠 とする）
            $configs = CommonConfig::getValues(['po_life_theater_limit_expand']);
            $po_cost     = isset($configs['po_life_theater_limit_expand']) ? (int)$configs['po_life_theater_limit_expand']->value1 : 300;
            $expandCount = 5;

            $result = DB::transaction(function () use ($user, $po_cost, $expandCount) {
                // ポイントチェック
                $check = $user->po_check($po_cost);
                if (!$check['can_use']) {
                    throw new \Exception('ポイントが不足しています。（必要: ' . $po_cost . 'pt）');
                }

                // ポイント消費
                $poResult = $user->po_use($po_cost, false, "ライフシアター 作品作成枠拡張 (+{$expandCount}枠)");
                if (!$poResult['success']) {
                    throw new \Exception('ポイントの引き落としに失敗しました。');
                }

                // ユーザーの拡張枠レコードを取得または作成
                $extraSlot = UserExtraSlot::firstOrCreate(
                    ['user_id' => $user->id],
                    ['life_theater_limit' => 0]
                );
                
                // 枠を加算して保存
                $extraSlot->life_theater_limit += $expandCount;
                $extraSlot->save();

                return [
                    'new_limit'   => $user->fresh()->getTheaterLimit(),
                    'total_point' => $user->fresh()->total_point
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => "作品の作成枠を {$expandCount} 件拡張しました！",
                'data'    => $result
            ]);

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            // Exceptionのメッセージが「ポイントが不足しています」等の場合はそのまま返す
            $msg = str_contains($e->getMessage(), 'ポイント') ? $e->getMessage() : '拡張処理中にエラーが発生しました。';
            return response()->json(['status' => 'error', 'message' => $msg], 400);
        }
    }
    /**
     * スライド作成枠を拡張する (Ajax - 作品単位)
     */
    public function expandSlideLimit(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $user = Auth::user();
            $theaterId = $request->input('life_theater_id');

            if (!$theaterId) {
                return response()->json(['status' => 'error', 'message' => '対象の作品が指定されていません。'], 400);
            }

            $theater = LifeTheater::find($theaterId);
            if (!$theater || ($theater->user_id !== $user->id && !LifeTheaterShare::checkEditPermission($theaterId, $user->id))) {
                return response()->json(['status' => 'error', 'message' => '権限がありません。'], 403);
            }

            $configs = CommonConfig::getValues(['po_life_theater_slide_expand']);
            $po_cost     = isset($configs['po_life_theater_slide_expand']) ? (int)$configs['po_life_theater_slide_expand']->value1 : 300;
            $expandCount = isset($configs['po_life_theater_slide_expand']) ? (int)$configs['po_life_theater_slide_expand']->value2 : 5;

            $result = DB::transaction(function () use ($user, $theater, $po_cost, $expandCount) {
                $check = $user->po_check($po_cost);
                if (!$check['can_use']) {
                    throw new \Exception('ポイントが不足しています。（必要: ' . $po_cost . 'pt）');
                }

                $poResult = $user->po_use($po_cost, false, "ライフシアター スライド枠拡張 (+{$expandCount}枚)");
                if (!$poResult['success']) {
                    throw new \Exception('ポイントの引き落としに失敗しました。');
                }

                $targetTheater = LifeTheater::lockForUpdate()->find($theater->id);
                $targetTheater->extra_slide_limit += $expandCount;
                $targetTheater->save();

                return [
                    'new_limit'   => $targetTheater->getSlideLimit(),
                    'total_point' => $user->fresh()->total_point
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => "スライド作成枠を {$expandCount} 枚拡張しました！",
                'data'    => $result
            ]);

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            $msg = str_contains($e->getMessage(), 'ポイント') ? $e->getMessage() : '拡張処理中にエラーが発生しました。';
            return response()->json(['status' => 'error', 'message' => $msg], 400);
        }
    }
    /**
     * オブジェクト作成枠を拡張する (Ajax - 作品単位)
     */
    public function expandObjectLimit(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $user = Auth::user();
            $theaterId = $request->input('life_theater_id');

            if (!$theaterId) {
                return response()->json(['status' => 'error', 'message' => '対象の作品が指定されていません。'], 400);
            }

            $theater = LifeTheater::find($theaterId);
            if (!$theater || ($theater->user_id !== $user->id && !LifeTheaterShare::checkEditPermission($theaterId, $user->id))) {
                return response()->json(['status' => 'error', 'message' => '権限がありません。'], 403);
            }

            $configs = CommonConfig::getValues(['po_life_theater_object_expand']);
            $po_cost     = isset($configs['po_life_theater_object_expand']) ? (int)$configs['po_life_theater_object_expand']->value1 : 100;
            $expandCount = isset($configs['po_life_theater_object_expand']) ? (int)$configs['po_life_theater_object_expand']->value2 : 2;

            $result = DB::transaction(function () use ($user, $theater, $po_cost, $expandCount) {
                $check = $user->po_check($po_cost);
                if (!$check['can_use']) {
                    throw new \Exception('ポイントが不足しています。（必要: ' . $po_cost . 'pt）');
                }

                $poResult = $user->po_use($po_cost, false, "ライフシアター オブジェクト枠拡張 (+{$expandCount}個)");
                if (!$poResult['success']) {
                    throw new \Exception('ポイントの引き落としに失敗しました。');
                }

                $targetTheater = LifeTheater::lockForUpdate()->find($theater->id);
                $targetTheater->extra_object_limit += $expandCount;
                $targetTheater->save();

                return [
                    'new_limit'   => $targetTheater->getObjectLimit(),
                    'total_point' => $user->fresh()->total_point
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => "オブジェクト作成枠を {$expandCount} 個拡張しました！",
                'data'    => $result
            ]);

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            $msg = str_contains($e->getMessage(), 'ポイント') ? $e->getMessage() : '拡張処理中にエラーが発生しました。';
            return response()->json(['status' => 'error', 'message' => $msg], 400);
        }
    }
}