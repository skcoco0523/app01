<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GameList;
use App\Models\GamePublisher;

class AdminGameController extends Controller
{
    //==================================================================================
    // ゲーム一覧・基本情報 (game/common/search)
    //==================================================================================
    public function game_index(Request $request)
    {
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();
        
        $keyword = [];
        $keyword['admin_flag']            = true;
        $games =  GameList::getGameList(99, false, 1, $keyword);

        $keyword['search_game_key']       = get_proc_data($input, "game_key");
        $activeGame =  GameList::getGameList(1, false, 1, $keyword)->first();
        return view('admin.admin_home', [
            'games'      => $games,
            'activeGame' => $activeGame,
            'input'      => $request->all(),
        ]);
    }

    public function game_update(Request $request)
    {
        $params = $request->only(['id', 'game_key', 'title', 'description', 'version', 'view_mode', 'orientation']);
        $params['enable_flag']     = $request->boolean('enable_flag', false);
        $params['login_user_flag'] = $request->boolean('login_user_flag', false);
        $params['admin_only_flag'] = $request->boolean('admin_only_flag', false);

        $result = GameList::chgGame($params);
        if (!$result['success']) {
            return redirect()->back()->with('msg', $result['msg'])->withInput();
        }
        return redirect()->route('admin.game.index', ['game_key' => $result['game']->game_key])->with('msg', $result['msg']);
    }

    public function game_destroy(Request $request)
    {
        $id = $request->input('id');
        $result = GameList::delGame($id);
        return redirect()->route('admin.game.index')->with('msg', $result['msg']);
    }

    //==================================================================================
    // 静的JSONファイルの一括パブリッシュ
    //==================================================================================
    public function publishGame($gameKey, $type = null, $targetKey = null)
    {
        // 個別パブリッシュ (スプライトシート)
        if ($type === 'sprite_sheet' && $targetKey) {
            $result = GamePublisher::publishSpriteSheet($gameKey, $targetKey);
            if ($result['success']) {
                return redirect()->back()->with('msg', "🟢 スプライトシート [{$targetKey}] の定義を個別に反映しました。");
            }
            return redirect()->back()->with('msg', "⚠️エラー: " . $result['msg']);
        }

        // 一括パブリッシュ (GamePublisher モデルに集約)
        $result = GamePublisher::publishAll($gameKey);
        
        return redirect()->back()->with('msg', $result['success'] ? "🟢 " . $result['msg'] : "⚠️ " . $result['msg']);
    }
}
