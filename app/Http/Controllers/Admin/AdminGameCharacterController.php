<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\GameList;
use App\Models\GameCharacter;
use App\Models\GameSpriteSheet;

class AdminGameCharacterController extends Controller
{
    protected $assetCategories = [
        'character'  => '👤 キャラクター (character)',
        'projectile' => '🏹 飛び道具・弾 (projectile)',
        'effect'     => '✨ エフェクト (effect)',
        'item'       => '🎒 アイテム・装備 (item)',
        'gimmick'    => '⚙️ ギミック・罠 (gimmick)',
        'tileset'    => '🧱 タイルセット・床 (tileset)',
        'background' => '🖼️ 背景・遠景 (background)',
        'ui'         => '📱 UI・システム (ui)'
    ];

    //==================================================================================
    // キャラクター管理 (game/character/search)
    //==================================================================================
    public function character_index(Request $request)
    {
        $keyword = [];
        $keyword['admin_flag'] = true;
        $games = GameList::getGameList(null, false, 1, $keyword);

        $gameKey = $request->input('game_key');
        
        $keyword = [];
        $keyword['search_game_key'] = $gameKey;
        $keyword['admin_flag'] = true;
        $game = GameList::getGameList(1, false, 1, $keyword)->first();

        $keyword = [];
        $keyword['search_game_id'] = $game ? $game->id : null;
        $keyword['search_name']    = $request->input('search');
        $keyword['search_type']    = $request->input('type');
        $keyword['admin_flag'] = true;
        $characters = GameCharacter::getCharacterList(null, false, 1, $keyword);

        return view('admin.admin_home', [
            'games'      => $games,
            //'game'       => $game,
            'gameKey'    => $gameKey,
            'characters' => $characters,
            'input'      => $request->all(),
        ]);
    }

    public function character_update(Request $request)
    {
        $gameKey = $request->input('game_key', 'twin_facer');
        $keyword = [];
        $keyword['search_game_key'] = $gameKey;
        $keyword['admin_flag'] = true;
        $game = GameList::getGameList(1, false, 1, $keyword)->first();
        if (!$game) return redirect()->back()->with('msg', 'エラー：対象のゲーム作品が見つかりません。');

        $params = $request->only(['id', 'character_key', 'name', 'type', 'sort_order']);
        $params['enable_flag']     = $request->boolean('enable_flag', false);
        $params['login_user_flag'] = $request->boolean('login_user_flag', false);
        $params['admin_only_flag'] = $request->boolean('admin_only_flag', false);

        if (empty($params['id'])) {
            $result = GameCharacter::createCharacter($params, $game->id);
        } else {
            $result = GameCharacter::chgCharacter($params);
        }

        return redirect()->back()->with('msg', $result['msg']);
    }

    //==================================================================================
    // 職人部屋 - 画像アセット管理 (game/asset)
    //==================================================================================
    public function asset_index(Request $request)
    {
        $games = GameList::getGameList();

        // character_idがあるなら、そのキャラの所属から親ゲームを逆引きする
        $characterId = $request->input('character_id');
        $character = null;
        $game = null;

        if ($characterId) {
            $character = GameCharacter::find($characterId);
            if ($character) {
                $game = GameList::find($character->game_id);
            }
        }

        if (!$game) {
            $gameKey = $request->input('game_key');
            if (!$gameKey && $games->first()) {
                $gameKey = $games->first()->game_key;
            }
            $keyword = [];
            $keyword['search_game_key'] = $gameKey;
            $keyword['admin_flag'] = true;
            $game = GameList::getGameList(1, false, 1, $keyword)->first();
        } else {
            $gameKey = $game->game_key;
        }

        $dbAssets = GameSpriteSheet::getSpriteSheetList();
        $images = $dbAssets->pluck('filename')->toArray();

        $keyword = [];
        $keyword['search_game_id'] = $game ? $game->id : null;
        $keyword['admin_flag'] = true;
        $gameCharacters = $game ? GameCharacter::getCharacterList(null, false, 1, $keyword) : [];

        $motionDataArr = [];
        if ($character) {
            $motionDataRaw = $character->motion_data;
            $motionDataArr = is_string($motionDataRaw) ? json_decode($motionDataRaw, true) : $motionDataRaw;
        }
        $motionContent = $character ? json_encode($motionDataArr, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : '';

        $activeFile = $request->input('file');
        if (empty($activeFile) && !empty($motionDataArr['setup']['parts'])) {
            foreach ($motionDataArr['setup']['parts'] as $part) {
                if (!empty($part['image'])) {
                    $activeFile = $part['image'];
                    break;
                }
            }
        }
        if (empty($activeFile)) {
            $activeFile = $images[0] ?? null;
        }

        $activeFileCategory = 'character';
        if ($activeFile) {
            $sheet = GameSpriteSheet::getSpriteSheetList(1, false, 1, ['filename' => $activeFile])->first();
            if ($sheet) $activeFileCategory = $sheet->category;
        }

        $atlasesMap = [];
        foreach ($dbAssets as $asset) {
            // キャラクターで利用するのは pixel_data
            $data = $asset->pixel_data ?? [];
            $data['category'] = $asset->category;
            $atlasesMap[$asset->filename] = $data;
        }

        $editorConfig = config('game.editor');

        return view('admin.admin_home', [
            'games'              => $games,
            'gameKey'            => $gameKey,
            'game'               => $game,
            'gameCharacters'     => $gameCharacters,
            'character'          => $character,
            'motionContent'      => $motionContent,
            'images'             => $images,
            'activeFile'         => $activeFile,
            'activeFileCategory' => $activeFileCategory,
            'atlasesMap'         => $atlasesMap,
            'categories'         => $this->assetCategories,
            'assets'             => $dbAssets,
            'atlasContent'       => '',
            'editorConfig'       => $editorConfig,
        ]);
    }

    public function asset_update(Request $request)
    {
        $characterId = $request->input('character_id');
        $gameKey = $request->input('game_key', 'twin_facer');
        $filename = $request->input('filename');

        if (!$characterId) {
            return redirect()->back()->with('msg', '⚠️エラー: モーションを保存するキャラクターが選択されていません。');
        }

        $motionContent = $request->input('motion_content');
        $motionData = !empty($motionContent) ? json_decode($motionContent, true) : [];
        if ($motionContent && $motionData === null) return redirect()->back()->with('msg', '形式エラー：モーションJSON');

        $character = GameCharacter::findOrFail($characterId);
        $character->motion_data = $motionData;
        $character->save();

        return redirect()->route('admin.game.asset.index', ['character_id' => $character->id, 'game_key' => $gameKey, 'file' => $filename])
            ->with('msg', "🟢 キャラクター「{$character->name}」のモーションデータを職人部屋に保存しました！");
    }
}
