<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

use App\Models\IotDevice;

//IOTデバイスコントローラー
class AdminIotDeviceController extends Controller
{
    //IoTデバイス追加　デバイスの追加は管理から行わない
    //IoTデバイス検索
    public function index(Request $request)
    {
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']            = true;
        $input['search_mac_addr']       = get_proc_data($input,"search_addr");
        $input['search_admin_user_id']  = get_proc_data($input,"search_owner_id");
        $input['search_type']           = get_proc_data($input,"search_type");
        $input['search_pincode']        = get_proc_data($input,"search_pincode");

        $input['page']                  = get_proc_data($input,"page");
        
        $iotdevice_list = IotDevice::getIotDeviceList(10,true,$input['page'],$input);  //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ

        //dd($iotdevice_list);
        $msg = request('msg');
        $msg = ($msg===NULL && $iotdevice_list === null) ? "検索結果が0件です。" : $msg;
        return view('admin.admin_home', compact('iotdevice_list', 'input', 'msg'));
    }
    //IoTデバイス削除
    public function destroy(Request $request)
    {
        $input = $request->all();
        //$msg = Music::delMusic($data['id']);
        $input['admin_flag']            = true;
        $ret = IotDevice::delIotDevice($input);
        if($ret['error_code']==0)     $msg = "デバイスを削除しました。";
        if($ret['error_code']==-1)    $msg = "デバイスの削除に失敗しました。";

        return redirect()->route('admin.iotdevice.index', ['input' => $input, 'msg' => $msg]);
    }
    //IoTデバイス変更
    public function update(Request $request)
    {
        $input = $request->all();
        $input['admin_flag']            = true;
        $input['id']                    = get_proc_data($input,"id");
        $input['mac_addr']              = get_proc_data($input,"mac_addr");
        $input['type']                  = get_proc_data($input,"device_type");
        $input['ver']                   = get_proc_data($input,"device_ver");
        $input['name']                  = get_proc_data($input,"name");

        $msg=null;
        if(!$input['id'])           $msg =  "テーブルから選択してください。";
        if(!$input['mac_addr'])     $msg =  "mac_addrは必須です。";
        if($msg!==null)         return redirect()->route('admin.iotdevice.index', ['input' => $input, 'msg' => $msg]);

        $ret = IotDevice::chgIotDevice($input);

        if($ret['error_code']==0){
            $msg = "デバイス情報を更新しました。";
        }else{
            $msg = "デバイスの更新に失敗しました。";
        }
        
        return redirect()->route('admin.iotdevice.index', ['input' => $input, 'msg' => $msg]);
    }

}
