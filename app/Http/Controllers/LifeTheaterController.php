<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB; // ★追加
use App\Models\LifeTheater;
use App\Models\LifeTheaterSlide;
use App\Models\LifeTheaterShare;
use App\Models\LifeTheaterSlideObject;
use App\Models\LifeTheaterMedia;
use App\Models\CommonConfig;
use App\Models\LifeTheaterPublicLink;
use App\Services\PointService; // ★追加

class LifeTheaterController extends Controller
{
    protected $pointService; // ★追加

    public function __construct(PointService $pointService) // ★PointServiceを注入
    {
        $this->pointService = $pointService;
    }

    /**
     * キャッシュ破棄用のヘルパーメソッド
     */
    public static function clearPlayCache($theater_id)
    {
        if ($theater_id) {
            Cache::forget('life_theater_play_data_' . $theater_id);
        }
    }

    public function index(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();

            $my_theater_list = LifeTheater::getLifeTheaterList(null, false, null, $input);
            $shared_theater_list = LifeTheaterShare::getSharedLifeTheaterList(null, false, null, $input);

            $my_theater_counts    = collect($my_theater_list)->groupBy('theme_color_num')->map->count();
            $share_theater_counts = collect($shared_theater_list)->groupBy('theme_color_num')->map->count();

            // 上限チェック用のデータ取得
            $user = Auth::user();
            $current_theater_count = LifeTheater::where('user_id', $user->id)->count();
            $max_theater_limit     = $user->getTheaterLimit();

            $common_conf_names = ['po_life_theater_limit_expand'];
            $configs = CommonConfig::getValues($common_conf_names);
            $po_life_theater_limit_cost   = $configs['po_life_theater_limit_expand']->value1 ?? 300;
            $po_life_theater_limit_amount = 5;

            return view('life_theater.index', compact(
                'my_theater_list', 'shared_theater_list', 
                'my_theater_counts', 'share_theater_counts',
                'current_theater_count', 'max_theater_limit',
                'po_life_theater_limit_cost', 'po_life_theater_limit_amount'
            ));
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->route('home')->with('error_msg', '一覧の取得に失敗しました。');
        }
    }

    /**
     * ライフシアター詳細・編集画面
     */
    public function show(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $share_flag = get_proc_data($input, "share_flag");

            if ($share_flag) {
                $input['search_life_theater_id'] = $id;
                $theater = LifeTheaterShare::getSharedLifeTheaterList(null, false, null, $input)->first();
            } else {
                $input['search_life_theater_id'] = $id;
                $theater = LifeTheater::getLifeTheaterList(null, false, null, $input)->first();
            }

            if (!$theater) {
                make_error_log($error_log, "Theater not found or permission denied. id:" . $id);
                return redirect()->route('life_theater.index')->with('error_msg', '対象のデータが存在しないか、アクセス権限がありません。');
            }

            $theater_id = $theater->id ?? $theater->life_theater_id;
            $slides = LifeTheaterSlide::getSlideList($theater_id);
            $media_list = LifeTheaterMedia::where('life_theater_id', $theater_id)->latest()->get();

            foreach ($slides as $slide) {
                $slide->parsed_config = LifeTheaterSlide::parseConfig($slide->config_data);
            }

            $config_definitions       = LifeTheater::getConfigDefinitions();
            $config_values            = LifeTheater::parseConfig($theater->config_data ?? null);
            $is_premium               = ($theater->plan_type ?? '') === 'premium';
            $slide_config_definitions = LifeTheaterSlide::getConfigDefinitions();
            $object_config_definitions = LifeTheaterSlideObject::getConfigDefinitions();

            // 発行済みの「広告なしURL数」を直接取得
            $used_ad_free_count = LifeTheaterPublicLink::where('life_theater_id', $theater_id)
                ->where('is_ad_free', true)
                ->count();

            // CommonConfigから設定値を取得
            $common_conf_names = [
                'po_life_theater_premium',
                'po_life_theater_url',
                'po_life_theater_library',
                'po_life_theater_slide_expand',
                'po_life_theater_object_expand',
            ];
            $configs = CommonConfig::getValues($common_conf_names);

            $po_life_theater_premium        = $configs['po_life_theater_premium']->value1 ?? 1000;
            $po_life_theater_url_cost       = $configs['po_life_theater_url']->value1 ?? 300;
            $po_life_theater_url_free_count = $configs['po_life_theater_url']->value2 ?? 1;
            $po_life_theater_library_cost   = $configs['po_life_theater_library']->value1 ?? 100;
            $po_life_theater_slide_cost     = $configs['po_life_theater_slide_expand']->value1 ?? 300;
            $po_life_theater_slide_amount   = $configs['po_life_theater_slide_expand']->value2 ?? 5;
            $po_life_theater_object_cost    = $configs['po_life_theater_object_expand']->value1 ?? 100;
            $po_life_theater_object_amount  = $configs['po_life_theater_object_expand']->value2 ?? 2;

            $theater_model = LifeTheater::find($theater_id);
            $max_slide_limit = $theater_model ? $theater_model->getSlideLimit() : 10;
            $max_object_limit = $theater_model ? $theater_model->getObjectLimit() : 2;
            
            $current_slide_count = count($slides);

            return view('life_theater.show', compact(
                'theater', 'slides', 'media_list', 'share_flag', 
                'config_definitions', 'config_values', 'is_premium',
                'slide_config_definitions', 'object_config_definitions',
                'po_life_theater_premium', 'po_life_theater_url_cost', 'po_life_theater_url_free_count',
                'used_ad_free_count','po_life_theater_library_cost',
                'po_life_theater_slide_cost', 'po_life_theater_slide_amount',
                'max_slide_limit', 'current_slide_count',
                'po_life_theater_object_cost', 'po_life_theater_object_amount', 'max_object_limit'
            ));
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->route('life_theater.index')->with('error_msg', '詳細の表示に失敗しました。');
        }
    }

    public function store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $user = Auth::user();
            $input['user_id'] = $user->id;

            $currentCount = LifeTheater::where('user_id', $user->id)->count();
            $limit = $user->getTheaterLimit();

            if ($currentCount >= $limit) {
                return redirect()->back()->with('error_msg', "作成可能な作品数の上限（{$limit}件）に達しています。枠を拡張してください。");
            }

            $ret = LifeTheater::createLifeTheater($input);

            if ($ret['error_code'] == 0) {
                return redirect()->route('life_theater.show', ['id' => $ret['id']])->with('success_msg', '作品を作成しました。');
            } else {
                return redirect()->back()->with('error_msg', '作成に失敗しました。');
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    public function update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $id    = get_proc_data($input, "id");
            $plan_type = get_proc_data($input, "plan_type");

            if ($plan_type) {
                $user = Auth::user();
                $common_conf_names = ['po_life_theater_premium'];
                $configs = CommonConfig::getValues($common_conf_names);
                $po_life_theater_premium = isset($configs['po_life_theater_premium']) ? (int)$configs['po_life_theater_premium']->value1 : 1000;

                // ★ DB::transaction でポイント消費とプラン更新をアトミックに実行
                $message = DB::transaction(function () use ($user, $id, $plan_type, $po_life_theater_premium) {
                    // 1. PointService で消費（事前チェック・ロット減算・ログ記録）
                    $poResult = $this->pointService->consume($user, $po_life_theater_premium, false, "ライフシアターpremiumパック");
                    if (!$poResult['success']) {
                        return make_message($poResult['message'], 'error');
                    }

                    // 2. プラン種別の更新
                    $ret = LifeTheater::chgLifeTheater(['id' => $id, 'plan_type' => $plan_type]);
                    if ($ret['error_code'] != 0) {
                        throw new \Exception('プラン更新処理に失敗しました。');
                    }

                    self::clearPlayCache($id);
                    return make_message('購入ありがとうございます。', 'success');
                });

                return redirect()->back()->with($message);

            } else {
                $theater = LifeTheater::find($input['id'] ?? null);
                if (!$theater) {
                    return redirect()->back()->with('error_msg', '対象データが存在しません。');
                }

                $isPremium     = ($theater->plan_type ?? '') === 'premium';
                $currentConfig = LifeTheater::parseConfig($theater->config_data ?? null);
                $inputConfig   = $input['config_data'] ?? [];
                $input['config_data'] = LifeTheater::filterConfigByPlan($inputConfig, $isPremium, $currentConfig);

                $ret = LifeTheater::chgLifeTheater($input);

                if ($ret['error_code'] == 0) {
                    self::clearPlayCache($input['id']);
                    return redirect()->back()->with('success_msg', '作品情報を更新しました。');
                } else {
                    return redirect()->back()->with('error_msg', '更新に失敗しました。');
                }
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    public function destroy(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $theater_id = $input['id'] ?? null;

            if ($theater_id) {
                LifeTheaterPublicLink::where('life_theater_id', $theater_id)->delete();
            }

            $ret = LifeTheater::delLifeTheater($input);

            if ($ret['error_code'] == 0) {
                self::clearPlayCache($theater_id);
                return redirect()->route('life_theater.index')->with('success_msg', '作品を削除しました。');
            } else {
                return redirect()->back()->with('error_msg', '削除に失敗しました。');
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    public function unshare(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $ret = LifeTheaterShare::delShareLifeTheater($input);

            if ($ret['error_code'] == 0) {
                return redirect()->route('life_theater.index')->with('success_msg', '共有を解除しました。');
            } else {
                return redirect()->back()->with('error_msg', '共有解除に失敗しました。');
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    public function slide_store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $theater = LifeTheater::find($input['life_theater_id'] ?? null);
            if (!$theater) {
                return redirect()->back()->with('error_msg', '対象の作品が見つかりません。');
            }

            $currentSlideCount = LifeTheaterSlide::where('life_theater_id', $theater->id)->count();
            $maxSlideLimit     = $theater->getSlideLimit();
            if ($currentSlideCount >= $maxSlideLimit) {
                return redirect()->back()->with('error_msg', "スライドの登録上限（{$maxSlideLimit}枚）に達しています。枠を拡張してください。");
            }

            $isPremium = ($theater->plan_type ?? '') === 'premium';
            $inputConfig = $input['config_data'] ?? [];
            $input['config_data'] = LifeTheaterSlide::filterConfigByPlan($inputConfig, $isPremium);

            $ret = LifeTheaterSlide::createSlide($input);

            if ($ret['error_code'] == 0) {
                self::clearPlayCache($input['life_theater_id'] ?? null);
                return redirect()->back()->with('success_msg', 'スライドを追加しました。');
            } else {
                return redirect()->back()->with('error_msg', 'スライドの追加に失敗しました。');
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    public function slide_update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $slide = LifeTheaterSlide::with('lifeTheater')->find($input['id'] ?? null);
            if (!$slide) {
                return redirect()->back()->with('error_msg', '対象のスライドが存在しません。');
            }

            $isPremium = ($slide->lifeTheater->plan_type ?? '') === 'premium';
            $currentConfig        = LifeTheaterSlide::parseConfig($slide->config_data ?? null);
            $inputConfig          = $input['config_data'] ?? [];
            $input['config_data'] = LifeTheaterSlide::filterConfigByPlan($inputConfig, $isPremium, $currentConfig);

            $ret = LifeTheaterSlide::chgSlide($input);

            if ($ret['error_code'] == 0) {
                self::clearPlayCache($slide->life_theater_id);
                return redirect()->back()->with('success_msg', 'スライドを更新しました。');
            } else {
                return redirect()->back()->with('error_msg', 'スライドの更新に失敗しました。');
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    public function slide_destroy(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $slide = LifeTheaterSlide::find($input['id'] ?? null);
            $theaterId = $slide ? $slide->life_theater_id : null;

            $ret = LifeTheaterSlide::delSlide($input);

            if ($ret['error_code'] == 0) {
                self::clearPlayCache($theaterId);
                return redirect()->back()->with('success_msg', 'スライドを削除しました。');
            } else {
                return redirect()->back()->with('error_msg', 'スライドの削除に失敗しました。');
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    public function slide_sort(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $slide_orders = $request->input('slide_orders', []);
            $theaterId = null;

            foreach ($slide_orders as $item) {
                if (!$theaterId) {
                    $slide = LifeTheaterSlide::find($item['id']);
                    $theaterId = $slide ? $slide->life_theater_id : null;
                }
                LifeTheaterSlide::chgSlide([
                    'id' => $item['id'],
                    'step_order' => $item['step_order']
                ]);
            }

            self::clearPlayCache($theaterId);
            return response()->json(['status' => 'success', 'message' => '並び順を更新しました。']);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '並び順の更新に失敗しました。'], 500);
        }
    }

    public function media_store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $input['user_id'] = Auth::id();

            $ret = LifeTheaterMedia::createMedia($input);

            if ($ret['error_code'] == 0) {
                return redirect()->back()->with('success_msg', 'ライブラリに画像を登録しました。');
            } else {
                return redirect()->back()->with('error_msg', '画像の登録に失敗しました。');
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    public function media_destroy(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $ret = LifeTheaterMedia::delMedia($input);

            if ($ret['error_code'] == 0) {
                return redirect()->back()->with('success_msg', 'ライブラリから画像を削除しました。');
            } else {
                return redirect()->back()->with('error_msg', '画像の削除に失敗しました。');
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    /**
     * ライフシアター再生画面（オーナー・共有者用）
     */
    public function play(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
            $share_flag = get_proc_data($input, "share_flag");

            if ($share_flag) {
                $input['search_life_theater_id'] = $id;
                $theater = LifeTheaterShare::getSharedLifeTheaterList(null, false, null, $input)->first();
            } else {
                $input['search_life_theater_id'] = $id;
                $theater = LifeTheater::getLifeTheaterList(null, false, null, $input)->first();
            }

            if (!$theater) {
                make_error_log($error_log, "Theater not found or permission denied. id:" . $id);
                return redirect()->route('life_theater.index')->with('error_msg', '対象のデータが存在しないか、アクセス権限がありません。');
            }

            $theater_id = $theater->id ?? $theater->life_theater_id;

            return $this->renderPlayView($theater, $theater_id, true, $share_flag);

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->route('life_theater.index')->with('error_msg', '再生画面の表示に失敗しました。');
        }
    }

    /**
     * 公開URL（トークン）経由での作品再生画面（未ログインアクセス可能）
     */
    public function public_play(Request $request, $token)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $link = LifeTheaterPublicLink::where('token', $token)->where('is_active', true)->first();

            if (!$link) {
                return response()->view('life_theater.public_error', [
                    'message' => '指定された公開URLは停止（削除）されたか、無効になっています。'
                ], 404);
            }

            if ($link->expires_at && $link->expires_at->isPast()) {
                return response()->view('life_theater.public_error', [
                    'message' => 'この公開URLは有効期限が切れています。'
                ], 410);
            }

            if (!empty($link->password)) {
                $sessionKey = 'lt_passcode_authed_' . $link->id;
                if (!session($sessionKey)) {
                    return view('life_theater.public_passcode', ['token' => $token]);
                }
            }

            $link->increment('view_count');

            $theater = LifeTheater::find($link->life_theater_id);
            if (!$theater) {
                return response()->view('life_theater.public_error', [
                    'message' => '対象の作品が存在しないか、削除されました。'
                ], 404);
            }

            return $this->renderPlayView($theater, $theater->id, (bool)$link->is_ad_free, 0);

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->view('life_theater.public_error', [
                'message' => '再生画面の表示中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * パスワードの検証処理
     */
    public function public_play_verify(Request $request, $token)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $link = LifeTheaterPublicLink::where('token', $token)->where('is_active', true)->first();

            if (!$link) {
                return redirect()->route('life_theater.public_play', ['token' => $token]);
            }

            $inputPassword = preg_replace('/[\p{Z}\p{Cc}]/u', '', $request->input('password', ''));

            if ($link->password === $inputPassword) {
                $sessionKey = 'lt_passcode_authed_' . $link->id;
                session([$sessionKey => true]);

                return redirect()->route('life_theater.public_play', ['token' => $token]);
            } else {
                return redirect()->back()->with('error_msg', '合言葉が正しくありません。');
            }

        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '認証処理中にエラーが発生しました。');
        }
    }

    /**
     * 再生用Viewデータをキャッシュ生成・返却する共通メソッド
     */
    private function renderPlayView($theater, $theater_id, $is_ad_free, $share_flag)
    {
        $cacheKey = 'life_theater_play_data_' . $theater_id;

        $playData = Cache::remember($cacheKey, 60, function () use ($theater, $theater_id) {
            $slides = LifeTheaterSlide::getSlideList($theater_id);

            $timeline = [];
            foreach ($slides as $index => $slide) {
                $rawObjects = $slide->objects ?? [];
                $stepTimings = [];
                $currentAccumulated = 0;

                foreach ($rawObjects as $stepIndex => $obj) {
                    $objConfig   = LifeTheaterSlideObject::parseConfig($obj->config_data ?? null);
                    $popDelay    = isset($objConfig['pop_delay']) ? ((float)$objConfig['pop_delay'] / 1000) : 0.5;
                    $popDuration = isset($objConfig['pop_duration']) ? ((float)$objConfig['pop_duration'] / 1000) : 2.5;

                    $startDelay = $currentAccumulated + $popDelay;
                    $currentAccumulated = $startDelay + $popDuration;

                    $stepTimings[$stepIndex] = [
                        'start_delay' => $startDelay,
                        'duration'    => $popDuration,
                        'pop_scale'   => $objConfig['emphasis_scale'] ?? '1.25',
                        'anim_style'  => $objConfig['animation_style'] ?? 'pop',
                        'balloon_pos' => $objConfig['balloon_position'] ?? 'auto',
                        'config'      => $objConfig,
                    ];
                }

                $uniqueCast = [];
                foreach ($rawObjects as $stepIndex => $obj) {
                    $personKey = $obj->name ?? 'unknown';

                    if (!isset($uniqueCast[$personKey])) {
                        $uniqueCast[$personKey] = [
                            'type'     => $obj->type ?? 'character',
                            'name'     => $obj->name ?? '',
                            'image'    => $obj->media->image_s3_key ?? null,
                            'speeches' => [],
                        ];
                    }

                    $timing = $stepTimings[$stepIndex] ?? [
                        'start_delay' => 0.5, 'duration' => 2.5, 'pop_scale' => '1.25',
                        'anim_style'  => 'pop', 'balloon_pos' => 'auto', 'config' => [],
                    ];

                    $uniqueCast[$personKey]['speeches'][] = [
                        'step'        => $stepIndex,
                        'text'        => $obj->text ?? '',
                        'name'        => $obj->name ?? '',
                        'type'        => $obj->type ?? 'character',
                        'image'       => $obj->media->image_s3_key ?? null,
                        'start_delay' => $timing['start_delay'],
                        'duration'    => $timing['duration'],
                        'pop_scale'   => $timing['pop_scale'],
                        'anim_style'  => $timing['anim_style'],
                        'balloon_pos' => $timing['balloon_pos'],
                        'config'      => $timing['config'],
                    ];
                }

                $timeline[] = [
                    'label'    => $slide->label ?? ($index + 1) . 'コマ',
                    'title'    => $slide->title ?? '',
                    'subtitle' => $slide->subtitle ?? '',
                    'text'     => $slide->content ?? '',
                    'image'    => $slide->media->image_s3_key ?? null,
                    'date'     => $slide->slide_date ?? '',
                    'cast'     => array_values($uniqueCast),
                    'config'   => LifeTheaterSlide::parseConfig($slide->config_data ?? null),
                ];
            }

            $configData = LifeTheater::parseConfig($theater->config_data ?? null);

            return [
                'slides'         => $slides,
                'timeline'       => $timeline,
                'titleSize'      => $configData['title_size'],
                'subtitleSize'   => $configData['subtitle_size'],
                'slideDuration'  => (int) $configData['slide_duration'],
                'showBrandBadge' => (bool) $configData['show_brand_badge'],
                'autoLoop'       => (bool) $configData['auto_loop'],
                'particleEffect' => $configData['particle_effect'],
            ];
        });

        return view('life_theater.play', array_merge($playData, [
            'theater'    => $theater,
            'share_flag' => $share_flag,
            'is_ad_free' => $is_ad_free
        ]));
    }
}