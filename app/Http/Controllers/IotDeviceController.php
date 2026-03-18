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
    //IoTデバイス詳細ページ
    public function show(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        $input['admin_flag']    = false;
        $input['id']            = $id;

        $input['search_id']  = $input['id'];
        $input['search_detail']  = true;
        $input['search_admin_uid']  = Auth::id();
        $iotdevice = IotDevice::getIotDeviceList(1,false,false,$input)->first();
        
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
        $input['name']              = get_proc_data($input,"iotdevice_name");
        $input['pincode']           = get_proc_data($input,"pincode");
        make_error_log($error_log,"user_id:".$user_id. " name:".$input['name']. " pincode:".$input['pincode']);

        if(Auth::user()->dev_reg_lock == 1){
            make_error_log($error_log,"dev_reg_lock");
            $message = make_message('連続で登録に失敗したためロックがかかっています。\n要望・問い合わせにて解除申請してください。', 'error');
        }
        if($input['pincode'] != null && $input['name'] != null && $user_id != null){
            $input['final_register_flag']        = true;
            $iotdevice = IotDevice::getIotDeviceList(1,false,null,$input)->first();  //仮登録デバイス検索

            if ($iotdevice !== null) {
                session()->forget(['iotdevice_error_count']);   //一致するデバイスがあったためエラー回数リセット
                //デイバス登録処理
                //$data = array("id" => $iotdevice->id, "name" => $input['name'], "admin_user_id" => $user_id, "pincode" => null);
                $data = array("id" => $iotdevice->id, "admin_user_id" => $user_id, "pincode" => null);
                $ret = IotDevice::chgIotDevice($data);
                make_error_log($error_log,"error_code:".$ret['error_code']);

                if($ret['error_code'] == 0){
                    Mosquitto::publishMQTT($iotdevice->mac_addr, "final_regist"); //登録完了通知
                    $message = make_message('デバイスを登録しました。', 'device_add');
                    //成功時はここでリダイレクト
                    return redirect()->route('remote.show', ['id' => $ret['id']])->with($message);
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
                    $message = make_message('該当のデバイスが存在しません。\nあと" . (10 - $errorCount) . "回でロックされます。', 'error');
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
        
        $input['admin_flag']        = false;
        $input['iotdevice_id']      = get_proc_data($input,"iotdevice_id");
        $input['iotdevice_name']    = get_proc_data($input,"iotdevice_name");
        //テーブル：virtual_remotesのid
        $input['search_admin_uid']  = Auth::id();
        $input['search_id']  = $input['iotdevice_id'];
        make_error_log($error_log,"iotdevice_id:".$input['iotdevice_id']. " iotdevice_name:".$input['iotdevice_name']);

        $iotdevice = IotDevice::getIotDeviceList(1,false,false,$input)->first();

        $message = make_message('更新に失敗しました。', 'error');
        if($iotdevice){
            if($input['iotdevice_name']){
                $ret = IotDevice::chgIotDevice(['id'=>$iotdevice->id, 'name'=>$input['iotdevice_name']]);
                make_error_log($error_log,"error_code:".$ret['error_code']);
                if($ret['error_code']==0){
                    $jdata = json_encode(["device_name" => $input['iotdevice_name']]);
                    Mosquitto::publishMQTT($iotdevice->mac_addr, "chg_device_name", $jdata); //情報変更通知
                    $message = make_message('更新しました。', 'device_chg');
                }
            }else{
                $message = make_message('デバイス名が入力されていません。', 'error');
            }
        }
        $input['id']    = $input['iotdevice_id'];
        return redirect()->route('iotdevice.show', ['id' => $input['iotdevice_id']])->with($message);

    }
    
    //IoTデバイス削除
    public function destroy(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']        = false;
        $input['iotdevice_id']      = get_proc_data($input,"iotdevice_id");
        $input['search_admin_uid']  = Auth::id();
        $input['search_id']         = $input['iotdevice_id'];
        make_error_log($error_log," user_id:".Auth::id()." iotdevice_id:".$input['iotdevice_id']);

        $iotdevice = IotDevice::getIotDeviceList(1,false,false,$input)->first();
        
        //所有者のみ削除可能
        $message = make_message('削除に失敗しました。', 'error');
        if($iotdevice){
            $ret = IotDevice::delIotDevice(['id'=>$iotdevice->id]);
            make_error_log($error_log,"error_code:".$ret['error_code']);
            if($ret['error_code']==0){
                $message = make_message('削除しました。', 'device_del');
            } 
        }
        return redirect()->route('remote.index')->with($message);            
    }
}

