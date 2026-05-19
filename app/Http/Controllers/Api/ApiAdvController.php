<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\AdvCategory;
use App\Models\AdvResearch;
use App\Models\AdvUserScore;
use App\Models\CommonConfig;
use App\Helpers\RakutenApiHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ApiAdvController extends Controller
{
    /**
     * 共通設定取得(広告関連)
     */
    public function api_adv_config()
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $common_conf_names = [
                'adv_score_select', 'adv_score_detail_view', 'adv_score_dislike', 'adv_score_bonus', 'adv_show_enable', 'adv_popup_interval'
            ];
            make_error_log($error_log, "calling getValues for names: " . implode(', ', $common_conf_names));
            $configs = CommonConfig::getValues($common_conf_names);
            
            $result = [
                '_version' => (int)Cache::get('common_config_version', 0)
            ];

            foreach ($configs as $config_name => $config_obj) {
                // $configs は [ 'name' => (object)... ] の形式
                $result[$config_name] = $config_obj;
            }

            make_error_log($error_log, "success. returning " . count($result) . " items");
            return response()->json($result);

        } catch (\Exception $e) {
            make_error_log($error_log, "Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * おすすめ広告取得
     */
    public function api_adv_get(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $input              = $request->all();
        $disp_cnt           = (int)get_proc_data($input, "disp_cnt", 3); // デフォルト3件
        try {
            make_error_log($error_log, "-------start------- disp_cnt={$disp_cnt}");
            $user = Auth::guard('sanctum')->user();
            make_error_log($error_log, "user_id=" . ($user ? $user->id : 'guest'));
            
            // 1. カテゴリの選定（異なるカテゴリを最大 disp_cnt 個選ぶ）
            $targetCategoryIds = [];
            
            if ($user) {
                // (A) 未接触カテゴリを優先（全体の半分程度を目安に最大選出）
                $scoredCategoryIds = AdvUserScore::where('user_id', $user->id)->pluck('adv_category_id')->toArray();
                $unscoredLimit = max(1, (int)($disp_cnt * 0.7)); // 例: 3件なら2件、1件なら1件
                
                $unscoredCategoryIds = AdvCategory::where('enable_flag', 1)
                    ->whereNotIn('id', $scoredCategoryIds)
                    ->inRandomOrder()
                    ->take($unscoredLimit)
                    ->pluck('id')
                    ->toArray();
                
                $targetCategoryIds = $unscoredCategoryIds;
                make_error_log($error_log, "selected unscored category IDs: " . implode(',', $targetCategoryIds));

                // (B) スコア上位のカテゴリから補充（合計 disp_cnt 個になるまで）
                if (count($targetCategoryIds) < $disp_cnt) {
                    $needed = $disp_cnt - count($targetCategoryIds);
                    $topCategoryIds = AdvUserScore::where('user_id', $user->id)
                        ->whereHas('category', function($q) { $q->where('enable_flag', 1); })
                        ->whereNotIn('adv_category_id', $targetCategoryIds)
                        ->orderBy('score', 'desc')
                        ->take($needed + 2) // 少し多めに取ってランダム性を持たせる
                        ->get()
                        ->pluck('adv_category_id')
                        ->toArray();
                    
                    if (!empty($topCategoryIds)) {
                        shuffle($topCategoryIds);
                        $addIds = array_slice($topCategoryIds, 0, $needed);
                        $targetCategoryIds = array_merge($targetCategoryIds, $addIds);
                        make_error_log($error_log, "added scored category IDs: " . implode(',', $addIds));
                    }
                }
            }

            // (C) それでも足りない場合は全有効カテゴリから補充
            if (count($targetCategoryIds) < $disp_cnt) {
                $needed = $disp_cnt - count($targetCategoryIds);
                $extraIds = AdvCategory::where('enable_flag', 1)
                    ->whereNotIn('id', $targetCategoryIds)
                    ->inRandomOrder()
                    ->take($needed)
                    ->pluck('id')
                    ->toArray();
                
                $targetCategoryIds = array_merge($targetCategoryIds, $extraIds);
                make_error_log($error_log, "final supplement from all categories: " . implode(',', $extraIds));
            }

            if (empty($targetCategoryIds)) {
                make_error_log($error_log, "no categories available");
                return response()->json([]);
            }

            // 2. 各カテゴリから1件ずつ広告取得
            $result = [];
            foreach ($targetCategoryIds as $catId) {
                $category = AdvCategory::find($catId);
                if (!$category) continue;

                $items = RakutenApiHelper::searchItems($category->search_keywords ?: $category->name, 10, $category->id);
                if (!empty($items)) {
                    // そのカテゴリの中からランダムに1件抽出
                    $item = $items[array_rand($items)];
                    if (isset($item['Item'])) {
                        $itemData = $item['Item'];
                        $result[] = [
                            'id' => $category->id,
                            'name' => $itemData['itemName'] ?? 'no name',
                            'href' => ($itemData['affiliateUrl'] ?? null) ?: ($itemData['itemUrl'] ?? '#'),
                            'src' => $itemData['mediumImageUrls'][0] ?? '',
                            'price' => $itemData['itemPrice'] ?? 0,
                            'shopName' => $itemData['shopName'] ?? '',
                        ];
                    }
                }
                
                if (count($result) >= $disp_cnt) break; // disp_cnt 件たまれば終了
            }

            make_error_log($error_log, "returning " . count($result) . " items from different categories");
            return response()->json($result);

        } catch (\Exception $e) {
            make_error_log($error_log, "Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 広告アクション記録（スコアリング）
     */
    public function api_adv_click(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $user = Auth::guard('sanctum')->user();
            if (!$user) {
                make_error_log($error_log, "Unauthorized");
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $categoryId = $request->input('adv_category_id');
            $type = $request->input('type'); // select / detail_view / dislike
            $seconds = $request->input('display_seconds', 0);

            make_error_log($error_log, "user_id={$user->id}, categoryId={$categoryId}, type={$type}, seconds={$seconds}");

            if (!$categoryId) {
                make_error_log($error_log, "Bad Request: missing categoryId");
                return response()->json(['error' => 'Bad Request'], 400);
            }

            // スコア計算 (DB設定値を使用)
            $scoreDelta = 0;
            $common_conf_names = [
                'adv_score_select', 'adv_score_detail_view', 'adv_score_dislike', 'adv_score_bonus'
            ];

            $configs = CommonConfig::getValues($common_conf_names);
            make_error_log($error_log, "configs loaded: " . json_encode($configs));

            if ($type === 'select') {
                // selectの場合は1秒以上表示された場合のみスコア加算（既存ロジック踏襲）
                if ($seconds >= 1) {
                    $scoreDelta = (int)$configs['adv_score_select']->value1;
                }
            } elseif ($type === 'detail_view') {
                $scoreDelta = (int)$configs['adv_score_detail_view']->value1;
            } elseif ($type === 'dislike') {
                $scoreDelta = (int)$configs['adv_score_dislike']->value1;
            }

            // 同一カテゴリ連続選択ボーナス (DB設定値を使用)
            $lastResearch = AdvResearch::where('user_id', $user->id)->orderBy('id', 'desc')->first();
            if ($lastResearch && $lastResearch->adv_category_id == $categoryId && $type === 'select') {
                $bonus = (int)$configs['adv_score_bonus']->value1;
                $scoreDelta += $bonus;
                make_error_log($error_log, "bonus applied: +{$bonus}");
            }

            make_error_log($error_log, "final scoreDelta: {$scoreDelta}");

            if ($scoreDelta != 0) {
                DB::transaction(function() use ($user, $categoryId, $scoreDelta, $seconds, $type) {
                    // 履歴保存
                    AdvResearch::create([
                        'user_id' => $user->id, 'adv_category_id' => $categoryId, 'display_seconds' => $seconds, 'score' => $scoreDelta, 'type' => $type,
                    ]);

                    // 累計スコア更新
                    $userScore = AdvUserScore::firstOrNew(['user_id' => $user->id, 'adv_category_id' => $categoryId,]);
                    $userScore->score += $scoreDelta;
                    $userScore->save();
                });
                make_error_log($error_log, "DB transaction completed");
            }

            return response()->json(['success' => true, 'new_score_delta' => $scoreDelta]);
        } catch (\Exception $e) {
            make_error_log($error_log, "Exception: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['error' => 'Internal Server Error', 'message' => $e->getMessage()], 500);
        }
    }
}
