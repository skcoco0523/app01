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
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //ユーザーのみ
        $this->middleware('auth');
    }

    //スマートリモコン一覧ページ
    public function index(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']    = false;
        $input['page']          = get_proc_data($input,"page");
        //$input['name_asc']      = true;

        $virtual_remote_list = VirtualRemoteUser::getVirtualRemoteUserList(null,false,null,$input);  //全件
        //dd($virtual_remote_list);
        
        $input['search_admin_uid']  = Auth::id();
        $input['type_asc']          = true;
        $iotdevice_list = IotDevice::getIotDeviceList(5,true,$input['page'],$input);  //5件
        
        //dd($iotdevice_list);

    
        $msg = null;
        //if($iotdevice){
            return view('remote.show', compact('iotdevice_list','virtual_remote_list', 'msg'));
        //}else{
            //return redirect()->route('home')->with('error', '該当の曲が存在しません');
        //}
    }
    //スマートリモコン詳細ページ
    public function show(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']    = false;
        $input['search_remote_id']    = $id;

        $virtual_remote = VirtualRemoteUser::getVirtualRemoteUserList(1,true,false,$input)->first();  //1件

        //dd($virtual_remote_list);
        if ($virtual_remote !== null) {
            $virtual_remote->blade_path = config('common.smart_remote_blade_paht') ."." . substr($virtual_remote->blade_name, 0, -6); 

            //デバイスの信号を取得
            $signal_list = IotDeviceSignal::where('device_id', $virtual_remote->remote_id)->get();

            $r_sig = [];
            foreach($signal_list as $signal){
                $r_sig[$signal->id] = $signal;
            }

            //dd($virtual_remote);

            $msg = null;
            return view('remote.detail', compact('virtual_remote', "r_sig", 'msg'));

        }else{
            //使用不可のため強制リダイレクト
            return redirect()->route('home');
        }
    }

    //スマートリモコン登録
    public function store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['remote_kind']           = get_proc_data($input,"remote_kind");
        $input['blade_id']              = get_proc_data($input,"blade_id");
        $input['remote_name']           = get_proc_data($input,"remote_name");

        $user_id = Auth::id();
        $input['admin_user_id'] = $user_id;

        make_error_log($error_log,"user_id:".$user_id);
        make_error_log($error_log,"remote_kind:".$input['remote_kind']. "    blade_id:".$input['blade_id']. "    remote_name:".$input['remote_name']);

        $ret = VirtualRemote::createVirtualRemote($input);

        if($ret['error_code'] == 0){
            make_error_log($error_log,"createVirtualRemote:success");

            //ユーザー個別リモコン作成　登録者はデフォルトで編集権限あり
            $ret2 = VirtualRemoteUser::createVirtualRemoteUser(['remote_id' => $ret['id'], 'user_id' => $user_id, 'admin_flag' => true,]);

            //test 強制エラー
            //$ret2['error_code'] = 1;
            if($ret2['error_code'] == 0){
                make_error_log($error_log,"createVirtualRemoteUser:success");

            }else{
                make_error_log($error_log,"createVirtualRemoteUser:failure");

                //ユーザー別リモコンの作成に失敗したため、仮想リモコン削除
                $ret3 = VirtualRemote::delVirtualRemote(['id' => $ret['id']]);
                if($ret3['error_code'] == 0){
                    make_error_log($error_log,"delVirtualRemoteUser:success");
                }else{
                    make_error_log($error_log,"delVirtualRemoteUser:failure inconsistency");
                }
            }

        }else{
            make_error_log($error_log,"createVirtualRemote:failure");
        }
        

        $msg = null;
        if($ret['error_code']==0 && $ret2['error_code']==0){
            $msg = "リモコンを追加しました。";
            $type = "remote_add";
        }else{
            $msg = "リモコンの追加に失敗しました。";
            $type = "error";
        }                        

        $message = ['message' => $msg, 'type' => $type, 'sec' => '2000'];
        make_error_log($error_log,"msg:".$msg);

        return redirect()->route('remote.index')->with($message);

    }
    //スマートリモコン変更
    public function update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log,"-----start-----");
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']        = false;
        $input['remote_id']         = get_proc_data($input,"remote_id");
        $input['remote_user_id']    = get_proc_data($input,"remote_user_id");
        //テーブル：virtual_remotesのid
        $input['remote_name']       = get_proc_data($input,"remote_name");

        $input['search_remote_id']  = $input['remote_user_id'];

        $type = "error";
        if(!$input['search_remote_id']){
            $msg = "情報が不足しています。";
            $message = ['message' => $msg, 'type' => $type, 'sec' => '2000'];
            make_error_log($error_log,"msg:".$msg);
            return redirect()->route('remote.index')->with($message);
        }
        $virtual_remote = VirtualRemoteUser::getVirtualRemoteUserList(1,true,false,$input)->first();  //1件
        
        if($virtual_remote->admin_flag){
            if($input['remote_name']){
                $input['id']    = get_proc_data($input,"remote_id");
                $ret = VirtualRemote::chgVirtualRemote(['id'=>$virtual_remote->remote_id, 'remote_name'=>$input['remote_name']]);
                if($ret['error_code']==0){
                    $msg = "更新しました。";
                    $type = "remote_chg";
                }else{
                    $msg = "更新に失敗しました。";
                }
            }else if($input['signal_name']){

            }
        }else{
            $msg = "リモコン編集の権限がありません。";
        }

        $input['id']    = get_proc_data($input,"search_remote_id");
        $message = ['message' => $msg, 'type' => $type, 'sec' => '2000'];

        return redirect()->route('remote.show', ['id' => $input['remote_id']])->with($message);

    }
    //スマートリモコン削除
    public function destroy(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']        = false;
        $input['remote_id']         = get_proc_data($input,"remote_id");
        $input['remote_user_id']    = get_proc_data($input,"remote_user_id");

        $input['search_id']         = $input['remote_id'];
        $virtual_remote = VirtualRemote::getVirtualRemoteList(1,true,false,$input)->first();  //1件
        
        //dd($virtual_remote,$input);
        //所有者のみ削除可能
        if($virtual_remote){
            $ret = VirtualRemote::delVirtualRemote(['id'=>$virtual_remote->id]);
            if($ret['error_code']==0){
                $msg = "削除しました。";
                $type = "remote_del";
            }else{
                $msg = "削除に失敗しました。";
                $type = "error";
            }    
        }else{
            $msg = "所有者のみ削除可能です。";
            $type = "error";
        }               

        $message = ['message' => $msg, 'type' => $type, 'sec' => '2000'];
        make_error_log($error_log,"msg:".$msg);

        return redirect()->route('remote.index')->with($message);

    }
    //スマートリモコン共有解除
    public function unshare(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']        = false;
        $input['remote_id']         = get_proc_data($input,"remote_id");
        $input['remote_user_id']    = get_proc_data($input,"remote_user_id");

        $input['search_remote_id']  = $input['remote_user_id'];
        $virtual_remote = VirtualRemoteUser::getVirtualRemoteUserList(1,true,false,$input)->first();  //1件
        //dd($virtual_remote,$input);
        
        $ret = VirtualRemoteUser::delVirtualRemoteUser(['id'=>$virtual_remote->id]);
        if($ret['error_code']==0){
            $msg = "共有解除しました。";
            $type = "remote_del";
        }else{
            $msg = "共有解除に失敗しました。";
            $type = "error";
        }                  

        $message = ['message' => $msg, 'type' => $type, 'sec' => '2000'];
        make_error_log($error_log,"msg:".$msg);

        return redirect()->route('remote.index')->with($message);

    }

}

