<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\IotDevice;
use App\Models\IotDeviceSignal;
use App\Models\VirtualRemote;
use App\Models\VirtualRemoteUser;
use App\Models\Mosquitto;



class SmartRemoteController extends Controller
{
    //スマートリモコン一覧ページ
    public function index(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
                
        $page          = get_proc_data($input,"page");
        $keyword = array(
            'admin_flag'        => false,
        );
        $virtual_remote_list = VirtualRemoteUser::getVirtualRemoteUserList(null,false,null,$keyword);  //全件
        
        $keyword = array(
            'admin_flag'        => false,
            'search_admin_uid'  => Auth::id(),
            'type_asc'          => true,
        );
        $iotdevice_list = IotDevice::getIotDeviceList(5, true, $page, $keyword);  //5件

        return view('remote.show', compact('iotdevice_list','virtual_remote_list'));
    }
    //スマートリモコン詳細ページ
    public function show(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $keyword = array(
            'admin_flag'        => false,
            'search_remote_id'  => $id,
        );
        $virtual_remote = VirtualRemoteUser::getVirtualRemoteUserList(1,true,false,$keyword)->first();  //1件

        if ($virtual_remote !== null) {
            $virtual_remote->blade_path = config('common.smart_remote_blade_paht') ."." . substr($virtual_remote->blade_name, 0, -6); 

            // DBから文字列で取得されている場合は配列にデコード
            if (is_string($virtual_remote->settings)) {
                $virtual_remote->settings = json_decode($virtual_remote->settings, true);
            }

            // 設定の初期値を保証
            if (empty($virtual_remote->settings)) {
                $virtual_remote->settings = [
                    'power' => true,
                    'temp'  => 25.0,
                    'mode'  => 'cool',
                    'fan'   => 'auto',
                    'swingv' => 'auto'
                ];
            }

            //デバイスの信号を取得
            // 仮想リモコンIDに紐づく信号を取得
            $signal_list = IotDeviceSignal::where('remote_id', $virtual_remote->remote_id)->get();

            $r_sig = [];
            foreach($signal_list as $signal){
                // ボタン番号をキーにして格納
                $r_sig[$signal->button_num] = $signal;
            }
            return view('remote.detail', compact('virtual_remote', "r_sig"));

        }else{
            $message = make_message('対象データがありません。', 'error'); 
            return redirect()->route('remote.index')->with($message);
        }
    }

    //スマートリモコン登録
    public function store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_user_id']         = Auth::id();
        $input['remote_kind']           = get_proc_data($input,"remote_kind");
        $input['blade_id']              = get_proc_data($input,"blade_id");
        $input['remote_name']           = get_proc_data($input,"remote_name");
        make_error_log($error_log,"user_id:".$input['admin_user_id']. "    remote_kind:".$input['remote_kind']. "    blade_id:".$input['blade_id']. "    remote_name:".$input['remote_name']);

        $ret = VirtualRemote::createVirtualRemote($input);
        make_error_log($error_log,"error_code:".$ret['error_code']);

        $message = make_message('リモコンの追加に失敗しました。', 'error'); 
        if($ret['error_code'] == 0){
            //ユーザー個別リモコン作成　登録者はデフォルトで編集権限あり
            $ret2 = VirtualRemoteUser::createVirtualRemoteUser(['remote_id' => $ret['id'], 'user_id' => $input['admin_user_id'], 'admin_flag' => true,]);
            make_error_log($error_log,"error_code2:".$ret2['error_code']);

            if($ret2['error_code'] == 0){
                $message = make_message('リモコンを追加しました。', 'remote_add'); 
            }else{
                //ユーザー別リモコンの作成に失敗したため、仮想リモコン削除
                $ret3 = VirtualRemote::delVirtualRemote(['id' => $ret['id']]);
                make_error_log($error_log,"error_code3:".$ret3['error_code']);
            }
        }

        return redirect()->route('remote.index')->with($message);

    }
    //スマートリモコン変更
    public function update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $remote_id          = get_proc_data($input,"remote_id");
        $search_remote_id   = $remote_id;
        $device_id          = get_proc_data($input,"device_id");
        $remote_user_id     = get_proc_data($input,"remote_user_id");
        $remote_name        = get_proc_data($input,"remote_name");

        make_error_log($error_log,"user_id:".Auth::id(). "    remote_id:".$remote_id. "    remote_user_id:".$remote_user_id. "    remote_name:".$remote_name);

        if($search_remote_id){
            $keyword = array(
                'admin_flag'        => false,
                'search_remote_id'  => $remote_id,
                'search_admin_uid'  => Auth::id(),
            );
            $virtual_remote = VirtualRemoteUser::getVirtualRemoteUserList(1,true,false,$keyword)->first();  //1件
            
            $message = make_message('更新に失敗しました。', 'error'); 
            if($virtual_remote->admin_flag){
                if($remote_name){
                    $input['id']    = get_proc_data($input,"remote_id");
                    $update_params = [
                        'id'            => $virtual_remote->remote_id,
                        'remote_name'   => $remote_name,
                        'device_id'     => $device_id
                    ];

                    $ret = VirtualRemote::chgVirtualRemote($update_params);
                    if($ret['error_code']==0){
                        $message = make_message('更新しました。', 'remote_chg'); 
                    }
                }
            }
            return redirect()->route('remote.show', ['id' => $remote_id])->with($message);
        }else{
            $message = make_message('情報が不足しています。', 'error'); 
            return redirect()->route('remote.index')->with($message);
        }
    }
    //スマートリモコン削除
    public function destroy(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']        = false;
        $input['remote_id']         = get_proc_data($input,"remote_id");
        $input['remote_user_id']    = get_proc_data($input,"remote_user_id");
        $input['search_id']         = $input['remote_id'];
        make_error_log($error_log,"user_id:".Auth::id(). "    remote_id:".$input['remote_id']. "    remote_user_id:".$input['remote_user_id']);

        $virtual_remote = VirtualRemote::getVirtualRemoteList(1,true,false,$input)->first();  //1件
        //所有者のみ削除可能
        $message = make_message('削除に失敗しました。', 'error'); 
        if($virtual_remote){
            $ret = VirtualRemote::delVirtualRemote(['id'=>$virtual_remote->id]);
            make_error_log($error_log,"error_code:".$ret['error_code']);
            if($ret['error_code']==0){
                $message = make_message('削除しました。', 'remote_del'); 
            } 
        }             
        return redirect()->route('remote.index')->with($message);
    }
    //スマートリモコン共有解除
    public function unshare(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']        = false;
        $input['remote_id']         = get_proc_data($input,"remote_id");
        $input['remote_user_id']    = get_proc_data($input,"remote_user_id");
        $input['search_remote_id']  = $input['remote_user_id'];
        make_error_log($error_log,"user_id:".Auth::id(). "    remote_id:".$input['remote_id']. "    remote_user_id:".$input['remote_user_id']);
        $virtual_remote = VirtualRemoteUser::getVirtualRemoteUserList(1,true,false,$input)->first();  //1件
        //dd($virtual_remote,$input);
        
        $ret = VirtualRemoteUser::delVirtualRemoteUser(['id'=>$virtual_remote->id]);
        make_error_log($error_log,"error_code:".$ret['error_code']);
        if($ret['error_code']==0){
            $message = make_message('共有解除しました。', 'remote_del'); 
        }else{
            $message = make_message('共有解除に失敗しました。', 'error');
        }                  
        return redirect()->route('remote.index')->with($message);
    }



}

