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
        // リダイレクト等の入力源を取得 ($input)
        if ($request->input('input') !== null) {
            $input = request('input');
        } else {
            $input = $request->all();
        }
        
        // 抽出・加工後のパラメータ配列を生成 ($params)
        $params = [];
        $params['admin_flag']       = true;
        $params['search_kind']      = get_proc_data($input, "search_kind");
        $params['search_name']      = get_proc_data($input, "search_name");
        $params['search_test_flag'] = get_proc_data($input, "search_test_flag");
        $params['search_library_flag'] = get_proc_data($input, "search_library_flag");
        $params['search_protocol'] = get_proc_data($input, "search_protocol");
        $params['page']             = get_proc_data($input, "page");
        
        $virtualremoteblade_list = VirtualRemoteBlade::getVirtualRemoteBladeList(10, true, $params['page'], $params);

        $msg = request('msg');
        $msg = ($msg === null && $virtualremoteblade_list === null) ? "検索結果が0件です。" : $msg;

        return view('admin.admin_home', [
            'virtualremoteblade_list' => $virtualremoteblade_list,
            'input'                   => $params, // 画面表示用に加工済みパラメータを渡す
            'msg'                     => $msg
        ]);
    }

    //リモコンデザイン追加画面
    public function create(Request $request)
    {
        $params = [
            'admin_flag' => true,
            'cdate_desc' => true
        ];
        $virtualremoteblade_list = VirtualRemoteBlade::getVirtualRemoteBladeList(5, false, null, $params);

        $msg = request('msg');
        if ($request->input('input') !== null) {
            $input = request('input');
        } else {
            $input = $request->all();
        }
        
        return view('admin.admin_home', [
            'virtualremoteblade_list' => $virtualremoteblade_list,
            'input'                   => $input,
            'msg'                     => $msg
        ]);
    }

    //リモコンデザイン追加
    public function store(Request $request)
    {
        $input = $request->all();
        
        $params = [];
        $params['admin_flag']   = true;
        $params['kind']         = get_proc_data($input, "remote_kind");
        $params['blade_name']   = get_proc_data($input, "blade_name") ?? '';
        $params['library_flag'] = get_proc_data($input, "library_flag") ?? 0;
        $params['protocol']     = get_proc_data($input, "protocol") ?? '';

        $msg = null;
        if (!isset($params['kind'])) {
            $msg = "リモコンの種別を選択してください";
        }
        if (empty($params['blade_name'])) {
            $msg = "ファイル名を入力してください。";
        } elseif (strlen($params['blade_name']) < 7 || substr($params['blade_name'], -6) !== '.blade') {
            $msg = "ファイル名は「XXXX.blade」の形式で入力してください。";
        }
        if (!$params['blade_name']) {
            $msg = "ファイル名を入力してください。";
        }

        if ($msg !== null) {
            return redirect()->route('admin.virtualremote.blade.create', ['input' => $input, 'msg' => $msg]);
        }

        // リモコン登録処理
        $ret = VirtualRemoteBlade::createVirtualRemoteBlade($params);

        if ($ret['error_code'] == 0) {
            $msg = "リモコン：{$params['blade_name']} を追加しました。";
            $input = null; // 登録成功時は入力初期化
        } else {
            if ($ret['error_code'] > 0)  $msg = "必須項目が不足しています。";
            if ($ret['error_code'] == -1) $msg = "リモコン：{$params['blade_name']} の追加に失敗しました。";
        }
        
        return redirect()->route('admin.virtualremote.blade.index', ['input' => $input, 'msg' => $msg]);
    }

    //リモコンデザイン削除
    public function destroy(Request $request)
    {
        $input = $request->all();
        $params = $input;
        $params['admin_flag'] = true;

        $ret = VirtualRemoteBlade::delVirtualRemoteBlade($params);
        $msg = ($ret['error_code'] == 0) ? "リモコンデザインを削除しました。" : "リモコンデザインの削除に失敗しました。";

        return redirect()->route('admin.virtualremote.blade.index', ['input' => $input, 'msg' => $msg]);
    }

    //リモコンデザイン変更
    public function update(Request $request)
    {
        $input = $request->all();
        
        $params = [];
        $params['admin_flag']   = true;
        $params['id']           = get_proc_data($input, "id");
        $params['kind']         = get_proc_data($input, "remote_kind");
        $params['blade_name']   = get_proc_data($input, "blade_name");
        $params['test_flag']    = get_proc_data($input, "test_flag");
        $params['library_flag'] = get_proc_data($input, "library_flag") ?? 0;
        $params['protocol']     = get_proc_data($input, "protocol") ?? '';

        $msg = null;
        if (!$params['id'])          $msg = "テーブルから選択してください。";
        if (!isset($params['kind'])) $msg = "種別は必須です。";
        if (!$params['blade_name'])  $msg = "ファイルは必須です。";

        if ($msg !== null) {
            return redirect()->route('admin.virtualremote.blade.index', ['input' => $input, 'msg' => $msg]);
        }

        $ret = VirtualRemoteBlade::chgVirtualRemoteBlade($params);
        $msg = ($ret['error_code'] == 0) ? "リモコンデザイン情報を更新しました。" : "リモコンデザインの更新に失敗しました。";
        
        return redirect()->route('admin.virtualremote.blade.index', ['input' => $input, 'msg' => $msg]);
    }

    //リモコンデザインプレビュー
    public static function preview(Request $request)
    {
        $input = $request->all();
        $preview = true;      

        $params = [];
        $params['admin_flag'] = true;
        $params['search_id']  = get_proc_data($input, "remoteblade_id");

        $virtualremoteblade_list = VirtualRemoteBlade::getVirtualRemoteBladeList(1, false, null, $params);
        
        $virtualremoteblade = $virtualremoteblade_list[0];
        $views_path = config('common.smart_remote_blade_paht') . "." . substr($virtualremoteblade->blade_name, 0, -6); 

        if (!View::exists($views_path)) {
            $views_path = null;
        }
        $virtualremoteblade->views_path = $views_path;

        return view('admin.admin_virtualremoteblade_preview', compact('virtualremoteblade', 'preview'));
    }
}