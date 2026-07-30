<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;

use App\Models\VirtualRemoteBlade;
use App\Models\IotDevice;
use App\Models\Mosquitto;
use App\Models\IotDeviceSignal;


class ApiSmartRemoteController extends Controller
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
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    
    //使用可能リモコンデザイン取得
    public function api_remote_blade_get(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $input = $request->all();
        
        $input['search_kind']           = get_proc_data($input,"search_kind");
        $blade_list = VirtualRemoteBlade::getVirtualRemoteBladeList(null,false,null,$input);
        $blade_list_array = [];

        foreach($blade_list as $key => $blade){
            $views_path = config('common.smart_remote_blade_paht') ."." . substr($blade->blade_name, 0, -6); 

            $data['id'] = $blade->id;
            if (View::exists($views_path)) {
                try {
                    $htmlContent = View::make($views_path)->render();
                } catch (\Exception $e) {
                    make_error_log($error_log,"error_mess". $e->getMessage());
                    $htmlContent = '<p style="color: red;">プレビューのレンダリングに失敗しました。</p>';
                }
            } else {
                make_error_log($error_log,"views_path:ng");
                $htmlContent = '<p style="color: orange;">デザインファイルが見つかりません。</p>';
            }
            $data['html_content'] = $htmlContent;           // レンダリング済みHTMLコンテンツ
            
            $blade_list_array[] = $data;
        }
        // JSON形式で返す
        return response()->json($blade_list_array);
    }
    //所有iotデバイス検索
    public function api_iot_devices_get(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-------start-------");

        $keyword = array(
            'admin_flag'        => false,
            'search_admin_uid'  => Auth::id(),
            'type_asc'          => true,
        );
        $iotdevice_list = IotDevice::getIotDeviceList(null, false, null, $keyword);  //全件
        $iotdevice_list_array = [];

        $data = [];
        foreach($iotdevice_list as $key => $device){
            $data = [
                'id'        => $device->id,
                'name'      => $device->name,
                'type'      => $device->type,
                'status'    => $device->status,
                'type_name' => $device->type_name,
            ];
            $iotdevice_list_array[] = $data;
        }

        make_error_log($error_log,"iotdevice_array:".print_r($iotdevice_list_array,1));
        // JSON形式で返す
        
        return response()->json($iotdevice_list_array);
    }

    // 赤外線受信待機リクエスト
    public function api_ir_receive_request(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");

        $device_id = $request->input('device_id');
        if (!$device_id) {
            return response()->json(['success' => false, 'msg' => 'デバイスIDが指定されていません。'], 400);
        }

        // デバイス取得（所有権確認）
        $device = IotDevice::where('id', $device_id)
                           ->where('admin_user_id', Auth::id())
                           ->first();

        if (!$device) {
            return response()->json(['success' => false, 'msg' => 'デバイスが見つからないか、権限がありません。'], 404);
        }

        // ステータスを「Requesting (2)」に更新
        $device->update(['status' => 2]);

        // ESP32にMQTT送信
        $ret = Mosquitto::publishMQTT($device->mac_addr, 'ir-receive-request');

        return response()->json($ret);
    }

    // デバイスステータス取得
    public function api_iot_device_status_get(Request $request)
    {
        $device_id = $request->input('device_id');
        $device = IotDevice::where('id', $device_id)
                           ->where('admin_user_id', Auth::id())
                           ->first();

        if (!$device) {
            return response()->json(['success' => false, 'msg' => 'デバイスが見つかりません。'], 404);
        }

        return response()->json([
            'success'         => true,
            'status'          => $device->status,
            'receive_command' => $device->receive_command,
            'receive_data'    => $device->receive_data,
        ]);
    }

    // 信号データの正式保存
    public function api_ir_signal_save(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");

        $data = $request->all();
        
        $device_id      = get_proc_data($data,"device_id");
        $remote_id      = get_proc_data($data,"remote_id");
        $button_num     = get_proc_data($data,"button_num");
        $category_name  = get_proc_data($data,"category_name");
        $signal_name    = get_proc_data($data,"signal_name");
        $signal_data    = get_proc_data($data,"signal_data");
        
        make_error_log($error_log, "device_id:".$device_id."  remote_id:".$remote_id."  button_num:".$button_num);
        make_error_log($error_log, "category_name:".$category_name."  signal_name:".$signal_name."  signal_data:".$signal_data);
        
        // 1. デバイスの所有権確認
        $device = IotDevice::where('id', $device_id )->where('admin_user_id', Auth::id())->first();

        if (!$device) {
            return response()->json(['success' => false, 'msg' => '権限がありません。'], 403);
        }

        // 2. IotDeviceSignal モデルを使用して保存 (アップサート)
        $ret = IotDeviceSignal::createIotDeviceSignal([
            'device_id'     => $device_id,
            'remote_id'     => $remote_id,
            'button_num'    => $button_num,
            'category_name' => $category_name ?? 'default',
            'signal_name'   => $signal_name ?? 'default',
            'signal_data'   => $signal_data,
        ]);

        if ($ret['success']) {
            // 保存に成功したら一時データをクリアし、ステータスを Online(1) に戻す
            $device->update([
                'status'          => 1,
                'receive_command' => null,
                'receive_data'    => null,
            ]);
        }else{
            make_error_log($error_log, "msg:".$ret['msg']);
        }

        return response()->json($ret);
    }

    // 赤外線信号送信 (通常送信 & テスト送信)
    public function api_ir_send(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");

        $device_id = $request->input('device_id');
        $signal_id = $request->input('signal_id');
        $test_flag = $request->input('test_flag');
        
        // 仮想リモコン経由の送信対応
        $remote_id = $request->input('remote_id');
        $button_num = $request->input('button_num');

        $raw_signal = null;
        $device = null;

        if ($test_flag) {
            // テスト送信：一時保存中の受信データを使用
            $device = IotDevice::where('id', $device_id)
                ->where('admin_user_id', Auth::id())
                ->first();
                
            if (!$device) {
                return response()->json(['success' => false, 'msg' => 'デバイスが見つかりません。'], 404);
            }

            $raw_signal = $device->receive_data;
            if (!$raw_signal) {
                return response()->json(['success' => false, 'msg' => 'テストする信号データがありません。'], 400);
            }
        } else {
            // 通常送信：保存済みの信号データを使用
            $signal = null;
            if ($signal_id) {
                $signal = IotDeviceSignal::find($signal_id);
            } elseif ($remote_id && $button_num) {
                // 仮想リモコンIDとボタン番号から検索
                $signal = IotDeviceSignal::where('remote_id', $remote_id)
                    ->where('button_num', $button_num)
                    ->first();
            }

            if (!$signal) {
                return response()->json(['success' => false, 'msg' => '指定された信号が見つかりません。'], 404);
            }

            $device = IotDevice::where('id', $signal->device_id)
                ->where('admin_user_id', Auth::id())
                ->first();

            if (!$device) {
                return response()->json(['success' => false, 'msg' => '送信デバイスが見つからないか権限がありません。'], 404);
            }

            $raw_signal = $signal->signal_data;
        }

        // 信号データの解析とフォーマット変換
        // raw_signal が JSON 文字列であることを想定
        $data_array = json_decode($raw_signal, true);
        $mqtt_payload = $raw_signal; // デフォルトはそのまま

        if (json_last_error() === JSON_ERROR_NONE && is_array($data_array)) {
            // 定義書 A. パルス配列指定
            if (isset($data_array['raw'])) {
                $mqtt_payload = [
                    'type' => 'raw',
                    'raw'  => $data_array['raw'],
                    'freq' => $data_array['freq'] ?? 38 //周波数
                ];
            }
            // 定義書 B. プロトコル・コード指定の場合はそのまま（または構造を維持）
            elseif (isset($data_array['protocol'])) {
                $mqtt_payload = [
                    'protocol' => $data_array['protocol'],
                    'hex'      => $data_array['hex'] ?? '0x0',
                    'bits'     => $data_array['bits'] ?? 0
                ];
            }
        }

        // ESP32にMQTT送信 (コマンドは ir-send)
        // $mqtt_payload が配列の場合は Mosquitto::publishMQTT 内で json_encode される
        $ret = Mosquitto::publishMQTT($device->mac_addr, 'ir-send', $mqtt_payload);

        return response()->json($ret);
    }
}
