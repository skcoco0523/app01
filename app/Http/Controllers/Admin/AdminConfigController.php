<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CommonConfig;

class AdminConfigController extends Controller
{
    public function __construct()
    {
        // 必要に応じてView変数を設定
    }

    // メンテナンス設定画面
    public function config_maint(Request $request)
    {
        $common_conf_names = [
            'maint_mode_flag'
        ];
        $configs = CommonConfig::getValues($common_conf_names);
        $config_type = 'maint';
        $msg = $request->query('msg');
        return view('admin.admin_home', compact('configs', 'config_type', 'msg'));
    }
    // MQTT設定画面
    public function config_mqtt(Request $request)
    {
        $common_conf_names = [
            'stop_admin_connect_notify','stop_admin_api_token'
        ];
        $configs = CommonConfig::getValues($common_conf_names);
        $config_type = 'mqtt';
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

        if($config_type=="maint") {
            return redirect()->route('admin.system.config_maint', ['msg' => '設定を更新しました。']);
        } elseif($config_type=="mqtt") {
            return redirect()->route('admin.system.config_mqtt', ['msg' => '設定を更新しました。']);
        }
    }
}
