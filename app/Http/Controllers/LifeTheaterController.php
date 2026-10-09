<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\LifeTheater;
use App\Models\LifeTheaterSlide;
use App\Models\LifeTheaterShare;
use App\Models\LifeTheaterMedia;

class LifeTheaterController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
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

            return view('life_theater.index', compact('my_theater_list', 'shared_theater_list', 'my_theater_counts', 'share_theater_counts'));
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

            // foreach で各スライドの設定値を安全に事前パースして保持
            foreach ($slides as $slide) {
                $slide->parsed_config = LifeTheaterSlide::parseConfig($slide->config_data);
            }

            // 設定フォーム用のデータ準備
            $config_definitions       = LifeTheater::getConfigDefinitions();
            $config_values            = LifeTheater::parseConfig($theater->config_data ?? null);
            $is_premium               = ($theater->plan_type ?? '') === 'premium';
            $slide_config_definitions = LifeTheaterSlide::getConfigDefinitions();

            return view('life_theater.show', compact(
                'theater', 'slides', 'media_list', 'share_flag', 
                'config_definitions', 'config_values', 'is_premium',
                'slide_config_definitions'
            ));
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->route('life_theater.index')->with('error_msg', '詳細の表示に失敗しました。');
        }
    }

    public function store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $input = $request->all();
            $input['user_id'] = Auth::id();

            $ret = LifeTheater::createLifeTheater($input);

            if ($ret['error_code'] == 0) {
                make_error_log($error_log, "success created id=" . $ret['id']);
                return redirect()->route('life_theater.show', ['id' => $ret['id']])->with('success_msg', '作品を作成しました。');
            } else {
                make_error_log($error_log, "create failed error_code=" . $ret['error_code']);
                return redirect()->back()->with('error_msg', '作成に失敗しました。');
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    /**
     * 作品の基本情報変更
     */
    public function update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");
        try {
            $input = $request->all();

            $theater = LifeTheater::find($input['id'] ?? null);
            if (!$theater) {
                return redirect()->back()->with('error_msg', '対象データが存在しません。');
            }

            $isPremium = ($theater->plan_type ?? '') === 'premium';

            $currentConfig        = LifeTheater::parseConfig($theater->config_data ?? null);
            $inputConfig          = $input['config_data'] ?? [];
            $input['config_data'] = LifeTheater::filterConfigByPlan($inputConfig, $isPremium, $currentConfig);

            $ret = LifeTheater::chgLifeTheater($input);

            if ($ret['error_code'] == 0) {
                return redirect()->back()->with('success_msg', '作品情報を更新しました。');
            } else {
                make_error_log($error_log, "update failed error_code=" . $ret['error_code']);
                return redirect()->back()->with('error_msg', '更新に失敗しました。');
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
            $ret = LifeTheater::delLifeTheater($input);

            if ($ret['error_code'] == 0) {
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

    /**
     * スライドの新規追加
     */
    public function slide_store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();

            // ★ 追加: 親作品のプランタイプを確認し、新規追加時の config_data をフィルタリング
            $theater = LifeTheater::find($input['life_theater_id'] ?? null);
            $isPremium = ($theater->plan_type ?? '') === 'premium';
            $inputConfig = $input['config_data'] ?? [];
            $input['config_data'] = LifeTheaterSlide::filterConfigByPlan($inputConfig, $isPremium);

            $ret = LifeTheaterSlide::createSlide($input);

            if ($ret['error_code'] == 0) {
                return redirect()->back()->with('success_msg', 'スライドを追加しました。');
            } else {
                return redirect()->back()->with('error_msg', 'スライドの追加に失敗しました。');
            }
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->back()->with('error_msg', '予期せぬエラーが発生しました。');
        }
    }

    /**
     * スライドの更新
     */
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
            $ret = LifeTheaterSlide::delSlide($input);

            if ($ret['error_code'] == 0) {
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

            foreach ($slide_orders as $item) {
                LifeTheaterSlide::chgSlide([
                    'id' => $item['id'],
                    'step_order' => $item['step_order']
                ]);
            }

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
     * ライフシアター再生画面（プレゼンテーション表示）
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
            $slides = LifeTheaterSlide::getSlideList($theater_id);

            $timeline = [];
            $timeline = [];
            foreach ($slides as $index => $slide) {
                $rawObjects = $slide->objects ?? [];
                
                // 同一人物（同じ画像 または 同じ名前）ごとにまとめる処理
                $uniqueCast = [];
                foreach ($rawObjects as $stepIndex => $obj) {
                    // 画像IDまたは名前をキーにして同一人物を識別
                    //$personKey = $obj->life_theater_media_id ? 'media_' . $obj->life_theater_media_id : 'name_' . ($obj->name ?? 'unknown');
                    //名前で固定する
                    $personKey = $obj->name ?? 'unknown';

                    if (!isset($uniqueCast[$personKey])) {
                        $uniqueCast[$personKey] = [
                            'type'     => $obj->type ?? 'character',
                            'name'     => $obj->name ?? '',
                            'image'    => $obj->media->image_s3_key ?? null,
                            'speeches' => [],
                        ];
                    }

                    $uniqueCast[$personKey]['speeches'][] = [
                        'step'  => $stepIndex,
                        'text'  => $obj->text ?? '',
                        'name'  => $obj->name ?? '',
                        'type'  => $obj->type ?? 'character',
                        'image' => $obj->media->image_s3_key ?? null, // ★ 会話ステップごとの画像を追加
                    ];
                }

                $timeline[] = [
                    'label'    => $slide->label ?? ($index + 1) . 'コマ',
                    'title'    => $slide->title ?? '',
                    'subtitle' => $slide->subtitle ?? '',
                    'text'     => $slide->content ?? '',
                    'image'    => $slide->media->image_s3_key ?? null,
                    'date'     => $slide->slide_date ?? '',
                    'cast'     => array_values($uniqueCast), // 配列化して渡す
                    'config'   => LifeTheaterSlide::parseConfig($slide->config_data ?? null),
                ];
            }

            $configData     = LifeTheater::parseConfig($theater->config_data ?? null);
            $titleSize      = $configData['title_size'];
            $subtitleSize   = $configData['subtitle_size'];
            $slideDuration  = (int) $configData['slide_duration'];
            $showBrandBadge = (bool) $configData['show_brand_badge'];
            $autoLoop       = (bool) $configData['auto_loop'];
            $particleEffect = $configData['particle_effect'];

            return view('life_theater.play', compact(
                'theater', 'timeline', 'slides', 'share_flag',
                'titleSize', 'subtitleSize', 'slideDuration',
                'showBrandBadge', 'autoLoop', 'particleEffect'
            ));
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->route('life_theater.index')->with('error_msg', '再生画面の表示に失敗しました。');
        }
    }
}