<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;

use App\Models\VirtualRemoteBlade;
use App\Models\IotDevice;

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
        $input = $request->all();
        
        $input['admin_flag']        = false;
        $input['search_admin_uid']  = Auth::id();
        $input['type_asc']          = true;
        $iotdevice_list = IotDevice::getIotDeviceList(null,false,null,$input);  //全件
        $iotdevice_list_array = [];

        $data = [];
        $device_info = config('common.device_info');
        foreach($iotdevice_list as $key => $device){
            $data = [
                'id'        => $device->id,
                'name'      => $device->name,
                'type'      => $device->type,
                'type_name' => $device->type_name,
            ];
            $iotdevice_list_array[] = $data;
        }

        make_error_log($error_log,"iotdevice_array:".print_r($iotdevice_list_array,1));
        // JSON形式で返す
        
        return response()->json($iotdevice_list_array);
    }
}
