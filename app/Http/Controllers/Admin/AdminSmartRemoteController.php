<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

use App\Models\VirtualRemote;
use App\Models\VirtualRemoteBlade;

//スマートリモコンコントローラー
class AdminSmartRemoteController extends Controller
{

    //リモコンデザイン検索
    public function index(Request $request)
    {
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $input['admin_flag']            = true;
        $input['search_kind']           = get_proc_data($input,"search_kind");
        $input['search_name']           = get_proc_data($input,"search_name");
        $input['search_test_flag']      = get_proc_data($input,"search_test_flag");

        $input['page']                  = get_proc_data($input,"page");
        
        $virtualremoteblade_list = VirtualRemoteBlade::getVirtualRemoteBladeList(10,true,$input['page'],$input);  //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ

        //dd($virtualremoteblade_list);
        $msg = request('msg');
        $msg = ($msg===NULL && $virtualremoteblade_list === null) ? "検索結果が0件です。" : $msg;
        return view('admin.admin_home', compact('virtualremoteblade_list', 'input', 'msg'));
    }
    //リモコンデザイン追加
    public function create(Request $request)
    {
        $input['admin_flag']            = true;
        $input['cdate_desc']            = true;
        $virtualremoteblade_list = VirtualRemoteBlade::getVirtualRemoteBladeList(5,false,null,$input);  //5件
        //dd($virtualremoteblade_list);
        $msg = request('msg');
        //追加からのリダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        return view('admin.admin_home', compact('virtualremoteblade_list', 'input', 'msg'));
    }
    //リモコンデザイン追加
    public function store(Request $request)
    {
        //$input = $request->only(['name', 'alb_id', 'art_id', 'art_name', 'release_date', 'link', 'aff_link']);
        $input = $request->all();
        
        $input['admin_flag']            = true;
        $input['kind']                  = get_proc_data($input,"remote_kind");
        $input['blade_name']            = get_proc_data($input,"blade_name")?? '';

        //dd($input);
        $msg=null;
        if(!isset($input['kind']))   $msg = "リモコンの種別を選択してください";
        // ファイル名の長さが6文字未満の場合、または後ろ6文字が '.blade' でない場合
        if (strlen($input['blade_name']) < 7 || substr($input['blade_name'], -6) !== '.blade') 
                                            $msg = "ファイル名は「XXXX.blade」の形式で入力してください。";
        if(!$input['blade_name'])           $msg = "ファイル名を入力してください。";
        if($msg!==null)         return redirect()->route('admin.virtualremote.blade.create', ['input' => $input, 'msg' => $msg]);

        
        //dd($input);
        //リモコン登録
        $ret = VirtualRemoteBlade::createVirtualRemoteBlade($input);
        if($ret['error_code']==0){
            $msg = "リモコン：{$input['blade_name']} を追加しました。";
            $input=null;   //データ登録成功時 初期化
        }else{
            if($ret['error_code']>0)      $msg = "必須項目が不足しています。";
            if($ret['error_code']==-1)    $msg = "リモコン：{$input['blade_name']} の追加に失敗しました。";
        }
        
        return redirect()->route('admin.virtualremote.blade.index', ['input' => $input, 'msg' => $msg]);
    }
    //リモコンデザイン削除
    public function destroy(Request $request)
    {
        $input = $request->all();
        //$msg = Music::delMusic($data['id']);
        $input['admin_flag']            = true;
        $ret = VirtualRemoteBlade::delVirtualRemoteBlade($input);
        if($ret['error_code']==0)     $msg = "リモコンデザインを削除しました。";
        if($ret['error_code']==-1)    $msg = "リモコンデザインの削除に失敗しました。";

        return redirect()->route('admin.virtualremote.blade.index', ['input' => $input, 'msg' => $msg]);
    }
    //リモコンデザイン変更
    public function update(Request $request)
    {
        $input = $request->all();
        $input['admin_flag']            = true;
        $input['id']                    = get_proc_data($input,"id");
        $input['kind']                  = get_proc_data($input,"remote_kind");
        $input['blade_name']            = get_proc_data($input,"blade_name");
        $input['test_flag']             = get_proc_data($input,"test_flag");

        $msg=null;
        if(!$input['id'])           $msg =  "テーブルから選択してください。";
        if(!isset($input['kind']))  $msg =  "種別は必須です。";
        if(!$input['blade_name'])   $msg =  "ファイルは必須です。";
        if($msg!==null)         return redirect()->route('admin.virtualremote.blade.index', ['input' => $input, 'msg' => $msg]);

        $ret = VirtualRemoteBlade::chgVirtualRemoteBlade($input);

        if($ret['error_code']==0){
            $msg = "リモコンデザイン情報を更新しました。";
        }else{
            $msg = "リモコンデザインの更新に失敗しました。";
        }
        
        return redirect()->route('admin.virtualremote.blade.index', ['input' => $input, 'msg' => $msg]);
    }
    //リモコンデザインプレビュー
    public static function preview(Request $request)
    {
        $input = $request->all();
        $preview = true;      

        $input['admin_flag']        = true;
        $input['search_id']         = get_proc_data($input,"remoteblade_id");
        $virtualremoteblade_list = VirtualRemoteBlade::getVirtualRemoteBladeList(1,false,null,$input);  //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ
        
        $virtualremoteblade = $virtualremoteblade_list[0];
        ///home/bitnami/htdocs/other/resources/views/smart_remote
        $views_path = config('common.smart_remote_blade_paht') ."." . substr($virtualremoteblade->blade_name, 0, -6); 

        //views/smart_remoteにあるかチェック　なければNULLにする
        if (View::exists($views_path)) {
        } else {
            $views_path = null; // Bladeファイルが存在しない場合
        }
        $virtualremoteblade->views_path = $views_path;

        return view('admin.admin_virtualremoteblade_preview', compact('virtualremoteblade','preview'));

    }
}
