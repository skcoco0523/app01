<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

use App\Models\LifeTheater;
use App\Models\LifeTheaterShare;
use App\Models\LifeTheaterSlideObject;

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
        $input     = $request->all();
        $type      = $request->route('type');
        make_error_log($error_log, "type:" . $type);

        // 1. 所有者チェック（操作対象の作品がログインユーザーの所有物か確認）
        $input['search_life_theater_id'] = get_proc_data($input, "life_theater_id");
        $theater                         = LifeTheater::getLifeTheaterList(null, false, null, $input)->first();
        if (!$theater) return false;

        // 2. 共有状態の確認
        $life_theater_id = get_proc_data($input, "life_theater_id");
        $friend_id       = get_proc_data($input, "friend_id");

        $sharing_theater = LifeTheaterShare::where('life_theater_id', $life_theater_id)
            ->where('user_id', $friend_id)
            ->first();

        // 操作タイプ別の事前ガードチェック
        if ($type == 'share'        && $sharing_theater)  return false;
        if ($type == 'unshare'      && !$sharing_theater) return false;
        if ($type == 'enable_edit'  && !$sharing_theater) return false;
        if ($type == 'disable_edit' && !$sharing_theater) return false;

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
            LifeTheaterShare::delShareLifeTheater($input);
        } elseif ($type == 'enable_edit') {
            LifeTheaterShare::where('id', $sharing_theater->id)->update(['admin_flag' => true]);
        } elseif ($type == 'disable_edit') {
            LifeTheaterShare::where('id', $sharing_theater->id)->update(['admin_flag' => false]);
        }

        return true;
    }

    /**
     * スライドオブジェクト一覧の取得
     */
    public function getSlideObjects(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $slide_id = $request->input('slide_id');
            if (!$slide_id) {
                return response()->json(['status' => 'error', 'message' => 'スライドIDが必要です。'], 400);
            }

            $objects = LifeTheaterSlideObject::where('life_theater_slide_id', $slide_id)
                ->with('media')
                ->orderBy('sort_order', 'asc')
                ->get();

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
        try {
            $input = $request->all();

            if (!empty($input['id'])) {
                // 更新
                $ret = LifeTheaterSlideObject::chgObject($input);
            } else {
                // 新規作成
                $ret = LifeTheaterSlideObject::createObject($input);
            }

            if ($ret['error_code'] == 0) {
                return response()->json(['status' => 'success', 'message' => 'オブジェクトを保存しました。']);
            }
            return response()->json(['status' => 'error', 'message' => '保存に失敗しました。'], 400);
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
        try {
            $input = $request->all();
            if (empty($input['id'])) {
                return response()->json(['status' => 'error', 'message' => 'オブジェクトIDが必要です。'], 400);
            }

            $ret = LifeTheaterSlideObject::delObject($input);

            if ($ret['error_code'] == 0) {
                return response()->json(['status' => 'success', 'message' => 'オブジェクトを削除しました。']);
            }
            return response()->json(['status' => 'error', 'message' => '削除に失敗しました。'], 400);
        } catch (\Exception $e) {
            make_error_log($error_log, "Error Message: " . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => '削除中にエラーが発生しました: ' . $e->getMessage()], 500);
        }
    }
}