<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AdvCategory;
use App\Models\CommonConfig;
use Illuminate\Support\Facades\View;

class AdminAdvController extends Controller
{
    public function __construct()
    {
        // 必要に応じてView変数を設定
    }

    // カテゴリ一覧・検索
    public function index(Request $request)
    {
        $input = $request->all();
        $query = AdvCategory::query();

        if (isset($input['name'])) {
            $query->where('name', 'like', '%' . $input['name'] . '%');
        }

        $categories = $query->orderBy('id', 'desc')->paginate(10);
        $msg = $request->query('msg');

        return view('admin.admin_home', compact('categories', 'input', 'msg'));
    }

    // カテゴリ登録・編集ページ
    public function create(Request $request)
    {
        $input = $request->all();
        $category = null;
        if (isset($input['id'])) {
            $category = AdvCategory::find($input['id']);
        }

        return view('admin.admin_home', compact('category', 'input'));
    }

    // 保存処理（登録・更新）
    public function store(Request $request)
    {
        $input = $request->all();
        $id = $request->input('id');

        $data = [
            'name' => $request->input('name'),
            'search_keywords' => $request->input('search_keywords'),
            'enable_flag' => $request->has('enable_flag') ? 1 : 0,
        ];

        if ($id) {
            AdvCategory::where('id', $id)->update($data);
            $msg = "カテゴリを更新しました。";
        } else {
            AdvCategory::create($data);
            $msg = "カテゴリを登録しました。";
        }

        return redirect()->route('admin.adv.index', ['msg' => $msg]);
    }

    // 削除処理
    public function destroy(Request $request)
    {
        $id = $request->input('id');
        if ($id) {
            AdvCategory::destroy($id);
            $msg = "カテゴリを削除しました。";
        } else {
            $msg = "削除対象が見つかりません。";
        }

        return redirect()->route('admin.adv.index', ['msg' => $msg]);
    }

    // 既存のupdateメソッドはstoreで兼ねるか、個別に実装
    public function update(Request $request)
    {
        return $this->store($request);
    }

    // 広告設定画面
    public function config(Request $request)
    {
        
        $common_conf_names = [
            'adv_score_select', 'adv_score_detail_view', 'adv_score_dislike', 'adv_score_bonus', 'adv_show_enable', 'adv_popup_interval'
        ];
        $configs = CommonConfig::getValues($common_conf_names);
        $msg = $request->query('msg');
        return view('admin.admin_home', compact('configs', 'msg'));
    }

    // 広告設定更新処理
    public function config_update(Request $request)
    {
        $input          = $request->all();
        $config_name    = get_proc_data($input,"config_name");
        $type           = get_proc_data($input,"type");
        $value1         = get_proc_data($input,"value1");
        $value2         = get_proc_data($input,"value2");
        $description    = get_proc_data($input,"description");

        CommonConfig::upsertValue($config_name, $type, $value1, $value2, $description);

        return redirect()->route('admin.adv.config', ['msg' => '設定を更新しました。']);
    }
}
