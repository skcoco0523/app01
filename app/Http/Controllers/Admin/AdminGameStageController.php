<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GameList;
use App\Models\GameStage;
use App\Models\GameMap;
use App\Models\GameSpriteSheet;
use App\Models\GameItem;

class AdminGameStageController extends Controller
{
    //==================================================================================
    // ステージ管理 (game/stage/search)
    //==================================================================================
    public function stage_index(Request $request)
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
        $stages = $game ? GameStage::getStageList(null, false, 1, $keyword) : [];

        $keyword = [];
        $keyword['search_game_id'] = $game ? $game->id : null;
        $keyword['admin_flag'] = true;
        $maps = $game ? GameMap::getMapList(null, false, 1, $keyword) : [];

        // スプライトシート一覧を取得（背景画像などの選択用）
        $spriteSheets = GameSpriteSheet::getSpriteSheetList(999, false, 1, ['admin_flag' => true]);

        // アイテム・ギミック・パーツ一覧を取得
        $keyword = [];
        $keyword['search_game_id'] = $game ? $game->id : null;
        $keyword['admin_flag'] = true;
        $gameItems = $game ? GameItem::getItemList(null, false, 1, $keyword) : [];

        return view('admin.admin_home', [
            'games'        => $games,
            'gameKey'      => $gameKey,
            'game'         => $game,
            'stages'       => $stages,
            'maps'         => $maps,
            'spriteSheets' => $spriteSheets,
            'gameItems'    => $gameItems,
            'input'        => $request->all(),
        ]);
    }

    public function stage_update(Request $request)
    {
        $gameKey = $request->input('game_key', 'twin_facer');
        $keyword = [];
        $keyword['search_game_key'] = $gameKey;
        $keyword['admin_flag'] = true;
        $game = GameList::getGameList(1, false, 1, $keyword)->first();
        if (!$game) return redirect()->back()->with('msg', 'エラー：対象のゲーム作品が見つかりません。');

        $params = $request->only(['id', 'map_id', 'type', 'number', 'name', 'custom_settings_json']);
        $params['enable_flag']     = $request->boolean('enable_flag', false);
        $params['login_user_flag'] = $request->boolean('login_user_flag', false);
        $params['admin_only_flag'] = $request->boolean('admin_only_flag', false);

        $result = GameStage::chgStage($params, $game->id);
        return redirect()->back()->with('msg', $result['msg']);
    }
}
