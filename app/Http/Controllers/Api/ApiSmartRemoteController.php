<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;

use App\Models\VirtualRemote;
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

    // デバイス疎通確認リクエスト (Ping)
    public function api_iot_device_ping(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "-------start-------");

        $device_id = $request->input('device_id');
        if (!$device_id) {
            return response()->json(['success' => false, 'msg' => 'デバイスIDが指定されていません。'], 400);
        }

        $device = IotDevice::getIotDeviceList(1,false,null,['search_id'=>$device_id, 'search_admin_uid'=>Auth::id()])->first();

        if (!$device) {
            return response()->json(['success' => false, 'msg' => 'デバイスが見つからないか、権限がありません。'], 404);
        }

        // ステータスを一旦「疎通確認中」に更新
        // ESPからの応答があれば Online に戻る仕組み
        IotDevice::chgIotDevice(['id' => $device_id, 'status' => config('common.iot_device_status.ping_requesting')]);

        // ESP32にMQTT送信
        $ret = Mosquitto::publishMQTT($device->mac_addr, 'ping');

        return response()->json($ret);
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
        $device = IotDevice::getIotDeviceList(1,false,null,['search_id'=>$device_id, 'search_admin_uid'=>Auth::id()])->first();

        if (!$device) {
            return response()->json(['success' => false, 'msg' => 'デバイスが見つからないか、権限がありません。'], 404);
        }

        // ステータスを「Requesting」に更新
        IotDevice::chgIotDevice(['id' => $device_id, 'status' => config('common.iot_device_status.requesting')]);

        // ESP32にMQTT送信
        $ret = Mosquitto::publishMQTT($device->mac_addr, 'ir-receive-request');

        return response()->json($ret);
    }

    // デバイスステータス取得
    public function api_iot_device_status_get(Request $request)
    {
        $device_id = $request->input('device_id');
        $device = IotDevice::getIotDeviceList(1,false,null,['search_id'=>$device_id, 'search_admin_uid'=>Auth::id()])->first();

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
        $device = IotDevice::getIotDeviceList(1,false,null,['search_id'=>$device_id, 'search_admin_uid'=>Auth::id()])->first();

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
            // 保存に成功したら一時データをクリアし、ステータスを Online に戻す
            IotDevice::chgIotDevice([
                'id'              => $device_id,
                'status'          => config('common.iot_device_status.online'),
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

        $remote_id      = $request->input('remote_id');
        $device_id      = $request->input('device_id');
        $test_flag      = $request->input('test_flag');  //仮保存信号でテスト
        
        $library_flag   = $request->input('library_flag');

        // ライブラリ送信用のパラメータ
        $protocol       = $request->input('protocol');
        $hex            = $request->input('hex');
        $bits           = $request->input('bits');
        
        // エアコン用追加パラメータ
        $temp           = $request->input('temp');
        $mode           = $request->input('mode');
        $fan            = $request->input('fan');
        $power          = $request->input('power');

        // 仮想リモコン経由の送信
        $button_num     = $request->input('button_num');

        $raw_signal     = null;
        $device         = null;

        $mqtt_payload = array();
        // ESPのライブラリを利用
        if($library_flag){
            
            make_error_log($error_log, "send_library");
            
            // 1. リクエストに device_id があればそれを使用
            if ($device_id) {
                $device = IotDevice::getIotDeviceList(1,false,null,['search_id'=>$device_id, 'search_admin_uid'=>Auth::id()])->first();
            } 
            // 2. なければリモコン設定 (virtual_remotes.device_id) を確認
            else if ($remote_id) {
                $v_remote = VirtualRemote::find($remote_id);
                if ($v_remote && $v_remote->device_id > 0) {
                    $device = IotDevice::getIotDeviceList(1,false,null,['search_id'=>$v_remote->device_id, 'search_admin_uid'=>Auth::id()])->first();
                    make_error_log($error_log, "found device from virtual_remote. device_id: ".$v_remote->device_id);
                }
            }

            // 3. それでもなければユーザーの最初の有効なデバイスを検索
            if (!$device) {
                $device = IotDevice::getIotDeviceList(1,false,null,['search_admin_uid'=>Auth::id(), 'search_status'=>config('common.iot_device_status.online')])->first();
                if ($device) make_error_log($error_log, "fallback to user first device. device_id: ".$device->id);
            }

            if (!$device) return response()->json(['success' => false, 'msg' => '送信デバイスが見つからないか権限がありません。'], 404);

            $mqtt_payload = [
                'type'     => 'library',
                'protocol' => $protocol,
            ];

            // エアコン用のパラメータがあれば追加
            if ($temp !== null)  $mqtt_payload['temp']  = (float)$temp;
            if ($mode !== null)  $mqtt_payload['mode']  = $mode;
            if ($fan !== null)   $mqtt_payload['fan']   = $fan;
            if ($request->has('swingv')) $mqtt_payload['swingv'] = $request->input('swingv');
            if ($request->has('clean'))  $mqtt_payload['clean']  = ($request->input('clean') === 'true' || $request->input('clean') === 1 || $request->input('clean') === "1" || $request->input('clean') === true) ? true : false;
            if ($power !== null) $mqtt_payload['power'] = ($power === 'false' || $power === 0 || $power === "0") ? false : true;

            // ライブラリ送信時かつリモコンIDがある場合、状態（settings）を保存
            if ($remote_id) {
                $v_remote = VirtualRemote::find($remote_id);
                if ($v_remote) {
                    $settings = $v_remote->settings ?? [];
            if ($temp !== null)  $settings['temp']  = (float)$temp;
            if ($mode !== null)  $settings['mode']  = $mode;
            if ($fan !== null)   $settings['fan']   = $fan;
            if ($request->has('swingv')) $settings['swingv'] = $request->input('swingv');
            if ($request->has('clean'))  $settings['clean']  = ($request->input('clean') === 'true' || $request->input('clean') === 1 || $request->input('clean') === "1") ? true : false;
            if ($power !== null) $settings['power'] = ($power === 'false' || $power === 0 || $power === "0") ? false : true;
                    
                    $v_remote->settings = $settings;
                    $v_remote->save();
                    make_error_log($error_log, "Updated remote settings: ".json_encode($settings));
                }
            }

            // Hexデータがあれば追加（テレビ・照明等）
            if ($hex !== null) {
                $mqtt_payload['hex']  = $hex;
                $mqtt_payload['bits'] = (int)$bits;
            }

        //ESPの学習データを利用
        }else{
            make_error_log($error_log, "send_learned data");
            if ($test_flag) {
                // テスト送信：一時保存中の受信データを使用
                $device = IotDevice::getIotDeviceList(1,false,null,['search_id'=>$device_id, 'search_admin_uid'=>AUth::id()])->first();
                if (!$device)       return response()->json(['success' => false, 'msg' => 'デバイスが見つかりません。'], 404);
                $raw_signal = $device->receive_data;
                if (!$raw_signal)   return response()->json(['success' => false, 'msg' => 'テストデータの受信、もしくは更新が失敗しています。'], 400);
                
            } else {
                // 通常送信：保存済みの信号データを使用
                $signal = null;
                if ($remote_id && $button_num) {
                    // 仮想リモコンIDとボタン番号から検索
                    $signal = IotDeviceSignal::where('remote_id', $remote_id)
                        ->where('button_num', $button_num)
                        ->first();
                }
                if (!$signal) return response()->json(['success' => false, 'msg' => '指定された信号が見つかりません。'], 404);   

                $device = IotDevice::getIotDeviceList(1,false,null,['search_id'=>$signal->device_id, 'search_admin_uid'=>AUth::id()])->first();
                if (!$device) return response()->json(['success' => false, 'msg' => '送信デバイスが見つからないか権限がありません。'], 404);
                
                $raw_signal = $signal->signal_data;
            }
            // 信号データの解析とフォーマット変換
            // raw_signal が JSON 文字列であることを想定
            $data_array = json_decode($raw_signal, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($data_array)) {
                // ESPで学習した信号
                if (isset($data_array['raw'])) {
                    $mqtt_payload = [
                        'type' => 'raw',
                        'raw'  => $data_array['raw'],
                        'freq' => $data_array['freq'] ?? $data_array['kHz'] ?? 38 //周波数 (freq または kHz、なければ38)
                    ];
                }
            }

        }

    make_error_log($error_log, "mqtt_payload:".print_r($mqtt_payload,1));
        
    if($mqtt_payload){
        // ESP32にMQTT送信 (コマンドは ir-send)
        // $mqtt_payload が配列の場合は Mosquitto::publishMQTT 内で json_encode される
        $ret = Mosquitto::publishMQTT($device->mac_addr, 'ir-send', $mqtt_payload);
        return response()->json($ret);

    }else{
        return ['success' => false, 'msg' => "送信に失敗しました。"];
    }



    }
}
