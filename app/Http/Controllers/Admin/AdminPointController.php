<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdvCategory;
use App\Models\CommonConfig;

class AdminPointController extends Controller
{
    public function __construct()
    {
        // 必要に応じてView変数を設定
    }

    // ポイント設定画面
    public function config(Request $request)
    {
        
        $common_conf_names = [
            'po_pack1', 'po_pack2', 'po_pack3', 'po_pack4', 'po_pack5', 'po_pack6',
            'po_free', 'po_free_flag'
        ];
        $configs = CommonConfig::getValues($common_conf_names);
        $msg = $request->query('msg');
        return view('admin.admin_home', compact('configs', 'msg'));
    }

    // ポイント設定更新処理
    public function config_update(Request $request)
    {
        $input          = $request->all();
        $config_name    = get_proc_data($input,"config_name");
        $type           = get_proc_data($input,"type");
        $value1         = get_proc_data($input,"value1");
        $value2         = get_proc_data($input,"value2");
        $description    = get_proc_data($input,"description");

        CommonConfig::upsertValue($config_name, $type, $value1, $value2, $description);

        return redirect()->route('admin.point.config', ['msg' => '設定を更新しました。']);
    }
}
