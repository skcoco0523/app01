<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GameList;
use App\Models\GameWeapon;
use App\Models\GameItem;

class AdminGameItemController extends Controller
{
    //==================================================================================
    // 武器・アイテム管理 (game/item/search)
    //==================================================================================
    public function item_index(Request $request)
    {
        $keyword = [];
        $games = GameList::getGameList();
        $gameKey = $request->input('game_key');
        $game_id = $request->input('game_id');

        $keyword['search_game_key'] = $gameKey;
        $keyword['admin_flag'] = true;
        $game = GameList::getGameList(1, false, 1, $keyword)->first();

        $keyword = [];
        $keyword['search_game_id'] = $game_id;
        $keyword['admin_flag'] = true;
        $weapons = GameWeapon::getWeaponList(null, false, 1, $keyword);

        $keyword = [];
        $keyword['search_game_id'] = $game_id;
        $keyword['admin_flag'] = true;
        $items   = GameItem::getItemList(null, false, 1, $keyword);

        return view('admin.admin_home', [
            'games'   => $games,
            'gameKey' => $gameKey,
            'game'    => $game,
            'weapons' => $weapons,
            'items'   => $items,
            'input'   => $request->all(),
        ]);
    }

    public function item_update(Request $request)
    {
        $gameKey = $request->input('game_key');
        $keyword = [];
        $keyword['search_game_key'] = $gameKey;
        $keyword['admin_flag'] = true;
        $game = GameList::getGameList(1, false, 1, $keyword)->first();
        if (!$game) return redirect()->back()->with('msg', 'エラー：対象のゲーム作品が見つかりません。');

        $itemType = $request->input('item_master_type');
        $params = $request->only(['id', 'weapon_key', 'item_key', 'name', 'type', 'sort_order', 'custom_settings_json']);
        $params['enable_flag']     = $request->boolean('enable_flag', false);
        $params['login_user_flag'] = $request->boolean('login_user_flag', false);
        $params['admin_only_flag'] = $request->boolean('admin_only_flag', false);

        if ($itemType === 'weapon') {
            $result = GameWeapon::saveWeapon($params, $game->id);
        } else {
            $result = GameItem::chgItem($params, $game->id);
        }
        return redirect()->back()->with('msg', $result['msg']);
    }
}
