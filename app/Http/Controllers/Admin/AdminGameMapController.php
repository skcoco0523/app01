<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GameList;
use App\Models\GameMap;
use App\Models\GameSpriteSheet;

class AdminGameMapController extends Controller
{
    public function map_index(Request $request)
    {
        $keyword = [];
        $games = GameList::getGameList();
        $gameKey = $request->input('game_key', 'twin_facer');

        $keyword['search_game_key'] = $gameKey;
        $keyword['admin_flag'] = true;
        $game = GameList::getGameList(1, false, 1, $keyword)->first();

        $keyword = [];
        $keyword['search_game_id'] = $game ? $game->id : null;
        $keyword['admin_flag'] = true;
        $maps = $game ? GameMap::getMapList(null, false, 1, $keyword) : [];

        $spriteSheets = GameSpriteSheet::getSpriteSheetList(999, false, 1, ['admin_flag' => true]);

        return view('admin.admin_home', [
            'games'        => $games,
            'gameKey'      => $gameKey,
            'game'         => $game,
            'maps'         => $maps,
            'spriteSheets' => $spriteSheets,
            'input'        => $request->all(),
        ]);
    }

    public function map_update(Request $request)
    {
        $gameKey = $request->input('game_key', 'twin_facer');
        $keyword = [];
        $keyword['search_game_key'] = $gameKey;
        $keyword['admin_flag'] = true;
        $game = GameList::getGameList(1, false, 1, $keyword)->first();
        if (!$game) return redirect()->back()->with('msg', 'エラー：対象のゲーム作品が見つかりません。');

        $params = $request->only(['id', 'map_key', 'name', 'custom_settings_json', 'thumbnail_url']);

        $result = GameMap::chgMap($params, $game->id);
        return redirect()->back()->with('msg', $result['msg']);
    }

    public function map_destroy(Request $request)
    {
        $id = $request->input('id');
        $map = GameMap::findOrFail($id);
        $name = $map->name;
        $map->delete();
        return redirect()->back()->with('msg', "マップ [{$name}] を削除しました。");
    }
}
