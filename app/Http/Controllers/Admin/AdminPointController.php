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

    // ポイントパック設定画面
    public function config_pack(Request $request)
    {
        // ポイントパック、購入額に対するポイント付与レート
        $common_conf_names = [
            'po_pack1', 'po_pack2', 'po_pack3', 'po_pack4', 'po_pack5', 'po_pack6', 'po_pack_late'
        ];
        $configs = CommonConfig::getValues($common_conf_names);
        $config_type = 'pack';
        $msg = $request->query('msg');
        return view('admin.admin_home', compact('configs', 'config_type', 'msg'));
    }
    // ポイント設定画面
    public function config_free(Request $request)
    {
        // 無料ポイント、広告視聴によるポイント付与数
        $common_conf_names = [
            'po_free', 'po_free_flag', 'po_ad_reward'
        ];
        $configs = CommonConfig::getValues($common_conf_names);
        $config_type = 'free';
        $msg = $request->query('msg');
        return view('admin.admin_home', compact('configs', 'config_type', 'msg'));
    }
    // ポイント額設定
    public function config_amount(Request $request)
    {
        //消費ポイント設定
        $common_conf_names = [
            'po_whisper', 'po_ai'
        ];
        $configs = CommonConfig::getValues($common_conf_names);
        $config_type = 'amount';
        $msg = $request->query('msg');
        return view('admin.admin_home', compact('configs', 'config_type', 'msg'));
    }

    // ポイント設定更新処理
    public function config_update(Request $request)
    {
        $input          = $request->all();
        $config_name    = get_proc_data($input,"config_name");
        $config_type    = get_proc_data($input,"config_type");
        $type           = get_proc_data($input,"type");
        $value1         = get_proc_data($input,"value1");
        $value2         = get_proc_data($input,"value2");
        $description    = get_proc_data($input,"description");

        CommonConfig::upsertValue($config_name, $type, $value1, $value2, $description);

        if($config_type=="pack") {
            return redirect()->route('admin.point.config_pack', ['msg' => '設定を更新しました。']);
        } elseif($config_type=="free") {
            return redirect()->route('admin.point.config_free', ['msg' => '設定を更新しました。']);
        } elseif($config_type=="amount") {
            return redirect()->route('admin.point.config_amount', ['msg' => '設定を更新しました。']);
        }
    }
}
