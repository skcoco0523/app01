<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Controllers\IotDeviceController;
use App\Models\IotDevice;

class ApiIotDeviceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 音声判定テスト API
     */
    public function api_ww_score_check(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-----start-----");

        try {
            $user_id = Auth::id();
            $iotdevice_id = $request->input('iotdevice_id');
            $ww_features_json = $request->input('ww_features'); // JSON string from JS: {features: [...], sampleCount: ...}

            if (!$iotdevice_id || !$ww_features_json) {
                return response()->json(['success' => false, 'msg' => 'データが不足しています。'], 400);
            }

            $keyword = [
                'admin_flag'        => false,
                'search_id'         => $iotdevice_id,
                'search_admin_uid'  => $user_id,
            ];
            $iotdevice = IotDevice::getIotDeviceList(1, false, false, $keyword)->first();

            if (!$iotdevice || !$iotdevice->ww_data) {
                return response()->json(['success' => false, 'msg' => '未登録です。'], 404);
            }

            $iotController = new IotDeviceController();
            
            // テストデータをデコード
            $test_data = json_decode($ww_features_json, true);

            // 登録データをデコード (新形式: prints配列の中に多次元データJSONが含まれる)
            $stored_ww_data = json_decode($iotdevice->ww_data, true);
            $prints_to_compare = $stored_ww_data['prints'] ?? [];

            $max_score = 0;
            foreach ($prints_to_compare as $stored_print_json) {
                $stored_data = json_decode($stored_print_json, true);
                
                // DTW マッチング実行
                $score = $iotController->ww_score_check_dtw($stored_data, $test_data);
                
                if ($score > $max_score) {
                    $max_score = $score;
                }
            }
            
            $score = round($max_score, 2);
            $is_match = ($score >= $iotdevice->ww_score);

            make_error_log($error_log, "API Match Score: " . $score . "%");

            return response()->json([
                'success' => true,
                'score'   => $score,
                'match'   => $is_match,
                'threshold' => $iotdevice->ww_score,
                'msg'     => "判定結果: " . $score . "% 一致しました。" . ($is_match ? "（合格）" : "（不合格）")
            ]);

        } catch (\Exception $e) {
            make_error_log($error_log, "Error: " . $e->getMessage());
            return response()->json(['success' => false, 'msg' => 'システムエラーが発生しました。'], 500);
        }
    }
}
