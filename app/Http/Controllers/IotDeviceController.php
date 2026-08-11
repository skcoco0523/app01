<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\UserLog;
use App\Models\IotDevice;
use App\Models\IotDeviceSignal;
use App\Models\VirtualRemote;
use App\Models\VirtualRemoteUser;
use App\Models\Mosquitto;



class IotDeviceController extends Controller
{
    /**
     * Voice match score threshold.
     */
    private const VOICE_MATCH_THRESHOLD = 80;

    //IoTデバイス詳細ページ
    public function show(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        $keyword = array(
            'admin_flag'        => false,
            'search_id'         => $id,
            'search_detail'     => true,
            'search_admin_uid'  => Auth::id(),
        );
        $iotdevice = IotDevice::getIotDeviceList(1, false, false, $keyword)->first();
        
        //対象デバイス所有者チェック
        if ($iotdevice !== null) {       
            //受信テスト
            //Mosquitto::sendMqttMessage($iotdevice->mac_addr, $ret['type'], $ret['mess']);
            $msg = null;
            return view('iotdevice.detail', compact('iotdevice', 'msg'));

        }else{
            $message = make_message('対象デバイスが存在しません。', 'error'); 
            return redirect()->route('remote.index')->with($message);
        }
    }

    //IoTデバイス登録(本登録)
    public function activate(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $user_id = Auth::id();
        $search_name              = get_proc_data($input,"iotdevice_name");
        $search_pincode              = get_proc_data($input,"pincode");
        make_error_log($error_log,"user_id:".$user_id. " name:".$search_name. " pincode:".$search_pincode);

        if(Auth::user()->dev_reg_lock == 1){
            make_error_log($error_log,"dev_reg_lock");
            $message = make_message('連続で登録に失敗したためロックがかかっています。\n要望・問い合わせにて解除申請してください。', 'error');
        }
        if($search_pincode != null && $search_name != null && $user_id != null){
            $keyword = array(
                'search_pincode'        => $search_pincode,
                'search_name'           => $search_name,
                'final_register_flag'   => true,
            );
            $iotdevice = IotDevice::getIotDeviceList(1, false, null, $keyword)->first();  //仮登録デバイス検索

            if ($iotdevice !== null) {
                session()->forget(['iotdevice_error_count']);   //一致するデバイスがあったためエラー回数リセット
                //デイバス登録処理
                //$data = array("id" => $iotdevice->id, "name" => $input['name'], "admin_user_id" => $user_id, "pincode" => null);
                $data = array("id" => $iotdevice->id, "admin_user_id" => $user_id, "pincode" => null);
                $ret = IotDevice::chgIotDevice($data);

                if($ret['success'] == true){
                    Mosquitto::publishMQTT($iotdevice->mac_addr, "final_regist"); //登録完了通知
                    $message = make_message('デバイスを登録しました。', 'device_add');
                    //成功時はここでリダイレクト
                    return redirect()->route('iotdevice.show', ['id' => $ret['id']])->with($message);
                }else{
                    $message = make_message('デバイスの登録に失敗しました。', 'error');
                }
            }else{
                // セッションにエラーカウントを保存
                $errorCount = session()->get('iotdevice_error_count', 0) + 1;
                session()->put('iotdevice_error_count', $errorCount);

                if ($errorCount >= 10) {
                    UserLog::create_user_log(Auth::id(),"dev_reg_lock");
                    User::chgProfile(["id" => $user_id ,"dev_reg_lock" => 1]);
                    session()->forget(['iotdevice_error_count']);   //エラー回数リセット
                    $message = make_message('デバイスが見つかりませんでした。\n10回連続で失敗したため、ロックがかかりました。', 'error');
                }else {
                    $message = make_message('該当のデバイスが存在しません。\nあと' . (10 - $errorCount) . '回でロックされます。', 'error');
                }
            }
        }else{
            $message = make_message('必要な情報が不足しています。', 'error');
        }
        return redirect()->route('remote.index')->with($message);
        
    }
    //IoTデバイス変更
    public function update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $iotdevice_id      = get_proc_data($input,"iotdevice_id");
        $iotdevice_name    = get_proc_data($input,"iotdevice_name");
        $ww_score          = get_proc_data($input,"ww_score");
        
        //テーブル：virtual_remotesのid
        $input['search_admin_uid']  = Auth::id();
        $input['search_id']  = $input['iotdevice_id'];
        make_error_log($error_log,"iotdevice_id:".$iotdevice_id. " iotdevice_name:".$iotdevice_name." ww_score:".$ww_score);

        $keyword = array(
            'admin_flag'        => false,
            'search_id'         => $iotdevice_id,
            'search_admin_uid'  => Auth::id(),
        );
        $iotdevice = IotDevice::getIotDeviceList(1, false, false, $keyword)->first();

        $message = make_message('更新に失敗しました。', 'error');
        if($iotdevice){
            if($iotdevice_name){
                $ret = IotDevice::chgIotDevice(['id'=>$iotdevice->id, 'name'=>$iotdevice_name, 'ww_score'=>$ww_score]);
                make_error_log($error_log,"success:".$ret['success']);
                if($ret['success']){
                    // デバイス名とスコア閾値のみ更新（音声は変更なしのため含めない）
                    $jdata = json_encode([
                        "device_name" => $iotdevice_name,
                        "ww_score" => (int)$ww_score
                    ]);
                    Mosquitto::publishMQTT($iotdevice->mac_addr, "update_device", $jdata);
                    $message = make_message($ret['msg'], 'device_chg');
                }
            }else{
                $message = make_message('デバイス名が入力されていません。', 'error');
            }
        }
        return redirect()->route('iotdevice.show', ['id' => $iotdevice_id])->with($message);

    }
    
    //IoTデバイス削除
    public function destroy(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $iotdevice_id      = get_proc_data($input,"iotdevice_id");
        make_error_log($error_log," user_id:". Auth::id(). " iotdevice_id:".$iotdevice_id);

        $keyword = array(
            'admin_flag'        => false,
            'search_id'         => $iotdevice_id,
            'search_admin_uid'  => Auth::id(),
        );
        $iotdevice = IotDevice::getIotDeviceList(1, false, false, $keyword)->first();
        
        //所有者のみ削除可能
        $message = make_message('削除に失敗しました。', 'error');
        if($iotdevice){
            $ret = IotDevice::delIotDevice(['id'=>$iotdevice->id]);
            make_error_log($error_log,"success:".$ret['success']);
            if($ret['success'] == true){
                $message = make_message($ret['msg'], 'device_del');
            } 
        }
        return redirect()->route('remote.index')->with($message);            
    }

    //音声テスト
    public function ww_score_check(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-----start-----");

        try {
            $user_id = Auth::id();
            $input = $request->all();
            $ww_features_json = $request->input('ww_features');
            
            $iotdevice_id = get_proc_data($input, "iotdevice_id");

            if ($iotdevice_id && $ww_features_json) {
                    $keyword = array(
                    'admin_flag'        => false,
                    'search_id'         => $iotdevice_id,
                    'search_admin_uid'  => Auth::id(),
                );
                $iotdevice = IotDevice::getIotDeviceList(1, false, false, $keyword)->first();
                
                if ($iotdevice && $iotdevice->ww_data) {
                    // 1. テスト音声をJSONから配列へ
                    $test_features = json_decode($ww_features_json, true);

                    // 2. DB内の登録済み指紋（最大3つ）を取得
                    $stored_ww_data = json_decode($iotdevice->ww_data, true);
                    // 後方互換性：古い形式(単一文字列)または新しい形式(prints配列)に対応
                    $prints_to_compare = [];
                    if (isset($stored_ww_data['prints']) && is_array($stored_ww_data['prints'])) {
                        $prints_to_compare = $stored_ww_data['prints'];
                    } elseif (is_string($stored_ww_data)) {
                        $prints_to_compare = [$stored_ww_data];
                    }

                    if (empty($prints_to_compare)) {
                        throw new \Exception("Stored voice print data is empty or malformed.");
                    }
                    
                    // --- モード選択 (strict: 声紋重視 / word: 単語重視) ---
                    $mode = 'word'; // ひとまず単語モードを優先
                    
                    if ($mode === 'strict') {
                        $test_print_str = $this->prepareFingerprintForDevice($test_features);
                    } else {
                        $test_print_str = $this->prepareWordPatternForDevice($test_features);
                    }

                    // 4. 登録されている複数の指紋とそれぞれ比較し、MAXスコアを取得する
                    $max_score = 0;
                    
                    foreach ($prints_to_compare as $stored_print_str) {
                        if ($mode === 'strict') {
                            // 従来のコサイン類似度比較
                            $stored_print_arr = explode(',', $stored_print_str);
                            $test_print_arr = explode(',', $test_print_str);
                            $best_sub_score = 0;
                            $count = count($stored_print_arr);
                            for ($offset = -5; $offset <= 5; $offset++) {
                                $dotProduct = 0; $normA = 0; $normB = 0;
                                for ($i = 0; $i < $count; $i++) {
                                    $j = $i + $offset;
                                    if ($j < 0 || $j >= $count) continue;
                                    $a = (int)$stored_print_arr[$i];
                                    $b = (int)$test_print_arr[$j];
                                    $dotProduct += $a * $b; $normA += $a * $a; $normB += $b * $b;
                                }
                                if ($normA > 0 && $normB > 0) {
                                    $current_sim = ($dotProduct / (sqrt($normA) * sqrt($normB))) * 100;
                                    if ($current_sim > $best_sub_score) $best_sub_score = $current_sim;
                                }
                            }
                        } else {
                            // 単語認識用（寛容）マッチング
                            $best_sub_score = $this->ww_score_check_word($stored_print_str, $test_print_str);
                        }
                        
                        if ($best_sub_score > $max_score) {
                            $max_score = $best_sub_score;
                        }
                    }
                    
                    $score = round($max_score, 2);
                    // ------------------------------------

                    make_error_log($error_log, "Match Score: " . $score . "%");
                    
                    // 80%以上を合格とする（閾値は運用に合わせて調整してください）
                    $type = ($score >= $iotdevice->ww_score) ? 'ww_test_ok' : 'ww_test_ng';
                    $message = make_message("判定結果: " . $score . "% 一致しました。", $type);
                    
                    return redirect()->route('iotdevice.show', ['id' => $iotdevice->id])->with($message);
                
                }
            }
            
            return redirect()->route('iotdevice.show', ['id' => $iotdevice_id ?? 0])->with(make_message('テストに失敗しました（データ不整合または未登録）。', 'error'));

        } catch (\Exception $e) {
            make_error_log($error_log, "Error: " . $e->getMessage());
            return redirect()->route('iotdevice.show');
        }
    }

    //音声指紋登録
    /**
     * Registers a voice print for an IoT device by averaging multiple samples.
     *
     * @param Request $request
     */
    public function set_ww_data(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        
        // $message の初期化（catch等での未定義エラー防止）
        $message = make_message('処理を開始できませんでした。', 'error');

        try {
            $user_id = Auth::id();
            $input = $request->all();
            $iotdevice_id = get_proc_data($input, "iotdevice_id");
            
            // ファイルではなくJSから送られてきたJSON文字列（特徴量リストの配列）を取得
            $ww_features_json = $request->input('ww_features_list');

            // 音声削除（クリア）処理
            if (isset($input['clear_voice']) && $input['clear_voice'] == '1') {
                $iotdevice = IotDevice::where('id', $iotdevice_id)->where('admin_user_id', $user_id)->first();
                if ($iotdevice) {
                    IotDevice::chgIotDevice(['id' => $iotdevice->id, 'ww_data' => null]);
                    // から配列で更新する
                    $jdata = json_encode(["ww_datas_b64" => []]); 
                    Mosquitto::publishMQTT($iotdevice->mac_addr, "update_ww_data", $jdata);
                    return redirect()->route('iotdevice.show', ['id' => $iotdevice->id])->with(make_message('音声データを削除しました。', 'device_del'));
                } else {
                    // Device not found or not owned for deletion
                    return redirect()->route('iotdevice.show', ['id' => $iotdevice_id ?? 0])
                                     ->with(make_message('音声データの削除に失敗しました。対象デバイスが見つからないか、所有者ではありません。', 'error'));
                }
            }

            $keyword = array(
                'admin_flag'        => false,
                'search_id'         => $iotdevice_id,
                'search_admin_uid'  => $user_id,
            );

            if ($iotdevice_id && $ww_features_json) {
                make_error_log($error_log, "Processing voice prints for device: " . $iotdevice_id);
                
                $iotdevice = IotDevice::getIotDeviceList(1, false, false, $keyword)->first();
                if ($iotdevice) {
                    // JSONをデコード（多次元データオブジェクトの配列）
                    $prints_list = json_decode($ww_features_json, true);

                    if (is_array($prints_list) && count($prints_list) > 0) {
                        $final_prints = [];
                        
                        foreach ($prints_list as $print_obj) {
                            if (empty($print_obj)) continue;
                            // 多次元データそのままをJSON化して配列に格納
                            $final_prints[] = json_encode($print_obj);
                        }

                        if (count($final_prints) > 0) {
                            $ww_data_data = json_encode([
                                "prints" => $final_prints
                            ]);
                        } else {
                            $ww_data_data = null;
                        }
                    } else {
                        $ww_data_data = null;
                    }

                    make_error_log($error_log,"final_ww_data_str:". ($ww_data_data ? 'SUCCESS' : 'FAILED'));

                    if ($ww_data_data) {
                        // モデルを使用してDB保存
                        $ret = IotDevice::chgIotDevice(['id' => $iotdevice->id, 'ww_data' => $ww_data_data]);

                        if ($ret['success']) {
                            // 音声指紋と現在のスコア閾値のみ更新（名前は変更なしのため含めない）
                            $b64_prints = IotDevice::getVoicePrintsB64($ww_data_data);
                            $jdata = json_encode([
                                "ww_score" => (int)$iotdevice->ww_score,
                                "ww_datas_b64" => $b64_prints
                            ]);
                            Mosquitto::publishMQTT($iotdevice->mac_addr, "update_device", $jdata);
                            
                            $message = make_message('音声指紋を登録しました。', 'device_chg');
                        } else {
                            $message = make_message('音声指紋のDB登録に失敗しました。', 'error');
                        }
                    } else {
                        $message = make_message('音声データの解析・平均化に失敗しました。', 'error');
                    }
                } else {
                    $message = make_message('該当するデバイスが見つかりません。', 'error');
                }
                
                return redirect()->route('iotdevice.show', ['id' => $iotdevice_id])->with($message);

            } else {
                $message = make_message('データが不足しています。', 'error');
            }
            return redirect()->route('iotdevice.show', ['id' => $iotdevice_id])->with($message);

        } catch (\Exception $e) {
            make_error_log($error_log, "Error: " . $e->getMessage());
            return redirect()->route('iotdevice.show', ['id' => $iotdevice_id])->with(make_message('システムエラーが発生しました。', 'error'));
        }
    }

    // Edge Impulse の特徴量配列（float）をデバイス（ESP32）向けに　0-255 の整数（カンマ区切り文字列）に変換する
    /**
     * Converts an array of float features from Edge Impulse to a comma-separated string of 0-255 integers for the device.
     * Optionally uses provided min/max values for normalization.
     *
     * @param array $features
     * @param float|null $normMin Optional: Minimum value for normalization. If null, calculated from features.
     * @param float|null $normMax Optional: Maximum value for normalization. If null, calculated from features.
     */
    public function prepareFingerprintForDevice($features)
    {
        if (!is_array($features) || empty($features)) {
            return null;
        }

        // 1. ノイズゲート：絶対値の平均が極めて低い場合（無音）は、全要素0にする
        $avg_abs = array_sum(array_map('abs', $features)) / count($features);
        if ($avg_abs < 0.001) {
            return implode(',', array_fill(0, count($features), 0));
        }

        // 2. 対称スケーリング
        $abs_max = 0;
        foreach ($features as $val) {
            if (abs($val) > $abs_max) $abs_max = abs($val);
        }

        if ($abs_max == 0) {
            return implode(',', array_fill(0, count($features), 0));
        }
        
        // 3. 移動平均（スムージング）をかけて、ノイズを除去
        $smoothed = [];
        $window = 2; // 解像度が上がったため窓を少し広げる
        $count = count($features);
        for ($i = 0; $i < $count; $i++) {
            $sum = 0; $div = 0;
            for ($j = -$window; $j <= $window; $j++) {
                if (isset($features[$i + $j])) {
                    $sum += $features[$i + $j]; $div++;
                }
            }
            $smoothed[] = $sum / $div;
        }

        // 4. Z-Score正規化
        $count = count($smoothed);
        $avg = $count > 0 ? array_sum($smoothed) / $count : 0;
        
        $variance = 0;
        foreach ($smoothed as $val) {
            $variance += pow($val - $avg, 2);
        }
        $std_dev = ($count > 0) ? sqrt($variance / $count) : 0;

        // 5. 正規化の実行と整数化（-128 〜 127）
        $final = array_map(function ($val) use ($avg, $std_dev) {
            if ($std_dev == 0) return 0;
            
            $z_score = ($val - $avg) / $std_dev;
            // 係数を調整してパターンの強調度を最適化
            $intVal = (int)round($z_score * 40);
            return max(-128, min(127, $intVal));
        }, $smoothed);

        return implode(',', $final);
    }

    /**
     * DTW (Dynamic Time Warping) based multi-dimensional sequence matching.
     * Handles variable length and speed.
     */
    public function ww_score_check_dtw($stored_data, $test_data)
    {
        $seqA = $stored_data['features']; // [[f1,f2,f3,f4,rms], ...]
        $seqB = $test_data['features'];
        
        $lenA = count($seqA);
        $lenB = count($seqB);
        if ($lenA === 0 || $lenB === 0) return 0;

        // 1. 時間差ペナルティの計算
        $timeA = $stored_data['sampleCount'] ?? ($lenA * 160);
        $timeB = $test_data['sampleCount'] ?? ($lenB * 160);
        $timeRatio = min($timeA, $timeB) / max($timeA, $timeB);
        // 長さが2倍以上違う場合は、DTWを計算するまでもなく大幅減点
        $timePenalty = ($timeRatio < 0.5) ? pow($timeRatio, 2) : 1.0;

        // 2. DTWコスト行列の初期化 (Memory考慮しつつ実装)
        $dtw = array_fill(0, $lenA + 1, array_fill(0, $lenB + 1, INF));
        $dtw[0][0] = 0;

        // 探索窓（Sakoe-Chiba Band）: 計算量削減と極端な歪みの抑制
        $window = max($lenA, $lenB) * 0.4; 

        for ($i = 1; $i <= $lenA; $i++) {
            for ($j = max(1, floor($i - $window)); $j <= min($lenB, floor($i + $window)); $j++) {
                // 多次元（5次元）ユークリッド距離の計算
                $dist = 0;
                for ($d = 0; $d < 5; $d++) {
                    $dist += pow(($seqA[$i-1][$d] - $seqB[$j-1][$d]) / 255.0, 2);
                }
                $cost = sqrt($dist);
                
                $dtw[$i][$j] = $cost + min($dtw[$i-1][$j], $dtw[$i][$j-1], $dtw[$i-1][$j-1]);
            }
        }

        // 3. スコア換算 (0-100)
        // 平均コストを計算
        $avgCost = $dtw[$lenA][$lenB] / max($lenA, $lenB);
        
        // 許容コスト閾値を大幅に引き下げ（0.5 -> 0.15）
        // 5次元(0-1.0)空間での距離なので、0.15はかなり厳しい（＝正確な）基準
        $threshold = 0.15;
        $rawScore = max(0, 100 * (1.0 - ($avgCost / $threshold))); 
        
        // 時間ペナルティを適用
        $finalScore = $rawScore * $timePenalty;

        return round($finalScore, 2);
    }

    /**
     * Converts float features to a word-pattern (0-255) focusing on rhythm/envelope.
     * Use this for speaker-independent word recognition.
     */
    public function prepareWordPatternForDevice($features)
    {
        if (!is_array($features) || empty($features)) return null;

        // 1. 対数変換で音量のダイナミックレンジを圧縮し、小さな声の変化を拾いやすくする
        $logged = array_map(function($v) {
            return log(abs($v) + 0.0001);
        }, $features);

        // 2. 移動平均（強めのスムージング）で声質を消し、「音の塊（言葉の区切り）」だけを抽出
        $smoothed = [];
        $window = 4; // 窓を広げて滑らかにする
        $count = count($logged);
        for ($i = 0; $i < $count; $i++) {
            $sum = 0; $div = 0;
            for ($j = -$window; $j <= $window; $j++) {
                if (isset($logged[$i + $j])) {
                    $sum += $logged[$i + $j]; $div++;
                }
            }
            $smoothed[] = $sum / $div;
        }

        // 3. Min-Max正規化 (0.0 〜 1.0)
        $min = min($smoothed);
        $max = max($smoothed);
        $range = ($max - $min) > 0 ? ($max - $min) : 1;

        $normalized = array_map(function($v) use ($min, $range) {
            return ($v - $min) / $range;
        }, $smoothed);

        // 4. 整数化 (0 〜 255)
        $final = array_map(function($v) {
            return (int)round($v * 255);
        }, $normalized);

        return implode(',', $final);
    }

}
