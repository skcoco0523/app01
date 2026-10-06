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
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware(['auth', 'verified']);
    }

    /**
     * ライフシアター一覧画面
     */
    public function index(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();

            // 自分の作品一覧取得
            $my_theater_list = LifeTheater::getLifeTheaterList(null, false, null, $input);

            // 共有されている作品一覧取得
            $shared_theater_list = LifeTheaterShare::getSharedLifeTheaterList(null, false, null, $input);

            // ナビ用の「色ごとの件数」を集計
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
                // 共有作品の取得と所有権チェック
                $input['search_life_theater_id'] = $id;
                $theater = LifeTheaterShare::getSharedLifeTheaterList(null, false, null, $input)->first();
            } else {
                // 自分の作品の取得
                $input['search_life_theater_id'] = $id;
                $theater = LifeTheater::getLifeTheaterList(null, false, null, $input)->first();
            }

            if (!$theater) {
                make_error_log($error_log, "Theater not found or permission denied. id:" . $id);
                return redirect()->route('life_theater.index')->with('error_msg', '対象のデータが存在しないか、アクセス権限がありません。');
            }

            $theater_id = $theater->id ?? $theater->life_theater_id;

            // スライド一覧の取得（リレーションで objects や media も一緒に取得可能）
            $slides = LifeTheaterSlide::getSlideList($theater_id);

            // ★ 作品固有の画像ライブラリ一覧を取得してビューへ渡す
            $media_list = LifeTheaterMedia::where('life_theater_id', $theater_id)->latest()->get();

            return view('life_theater.show', compact('theater', 'slides', 'media_list', 'share_flag'));
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->route('life_theater.index')->with('error_msg', '詳細の表示に失敗しました。');
        }
    }

    /**
     * 作品の新規登録
     */
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

            // freeプラン（非プレミアム）の場合は、強制的に標準デフォルト値をセット
            if (!$theater || ($theater->plan_type ?? '') !== 'premium') {
                $input['config_data'] = LifeTheater::DEFAULT_CONFIG;
            }

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

    /**
     * 作品の削除
     */
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

    /**
     * 共有解除（共有された側からの離脱）
     */
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
     * スライドの新規追加（life_theater_media_id を受容）
     */
    public function slide_store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
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
     * スライドの更新（life_theater_media_id を受容）
     */
    public function slide_update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $input = $request->all();
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

    /**
     * スライドの削除
     */
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

    /**
     * スライドの並び順一括更新（AJAX / POST用）
     */
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

    /**
     * ★ メディア（画像ライブラリ）のフォーム送信追加
     */
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

    /**
     * ★ メディア（画像ライブラリ）の削除
     */
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
                // 共有作品の取得と権限チェック
                $input['search_life_theater_id'] = $id;
                $theater = LifeTheaterShare::getSharedLifeTheaterList(null, false, null, $input)->first();
            } else {
                // 自分の作品の取得
                $input['search_life_theater_id'] = $id;
                $theater = LifeTheater::getLifeTheaterList(null, false, null, $input)->first();
            }

            if (!$theater) {
                make_error_log($error_log, "Theater not found or permission denied. id:" . $id);
                return redirect()->route('life_theater.index')->with('error_msg', '対象のデータが存在しないか、アクセス権限がありません。');
            }

            $theater_id = $theater->id ?? $theater->life_theater_id;

            // モデルのメソッド経由でスライド一覧を取得
            $slides = LifeTheaterSlide::getSlideList($theater_id);

            // 再生画面用のタイムラインデータ構造に整形
            $timeline = [];
            $timeline = [];
            foreach ($slides as $index => $slide) {
                // スライドに紐づくオブジェクト（キャスト・吹き出し）の整形
                $objectsData = [];
                if ($slide->objects) {
                    foreach ($slide->objects as $obj) {
                        $objectsData[] = [
                            'type'  => $obj->type ?? 'cast',
                            'name'  => $obj->name ?? '',
                            'text'  => $obj->text ?? '',
                            'image' => $obj->media->image_s3_key ?? null,
                        ];
                    }
                }

                $timeline[] = [
                    'label'    => $slide->label ?? ($index + 1) . 'コマ',
                    'month'    => $index,
                    'title'    => $slide->title ?? '',
                    'subtitle' => $slide->subtitle ?? '',
                    'text'     => $slide->content ?? '',
                    'image'    => $slide->media->image_s3_key ?? null,
                    'date'     => $slide->slide_date ?? '',
                    'objects'  => $objectsData, // ★ オブジェクト配列を追加
                ];
            }

            // ▼▼ config_dataの補正・初期値適用（Controller側で実施） ▼▼
            $defaultConfig = LifeTheater::DEFAULT_CONFIG;
            $configData    = is_array($theater->config_data) ? $theater->config_data : json_decode($theater->config_data ?? '[]', true);

            $titleSize     = $configData['title_size'] ?? $defaultConfig['title_size'];
            $subtitleSize  = $configData['subtitle_size'] ?? $defaultConfig['subtitle_size'];
            $slideDuration = (int)($configData['slide_duration'] ?? $defaultConfig['slide_duration']);

            return view('life_theater.play', compact(
                'theater', 'timeline', 'slides', 'share_flag',
                'titleSize', 'subtitleSize', 'slideDuration'
            ));
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return redirect()->route('life_theater.index')->with('error_msg', '再生画面の表示に失敗しました。');
        }
    }
}