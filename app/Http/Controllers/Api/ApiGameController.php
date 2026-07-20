<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\GameList;
use App\Models\GameCharacter;
use App\Models\GameSpriteSheet;

class ApiGameController extends Controller
{
    /**
     * Helper method to retrieve and decode a JSON file from storage.
     *
     * @param string $gameKey The key of the game.
     * @param string $fileName The name of the JSON file (without .json extension).
     * @param string $subPath Optional sub-directory within the game's public folder.
     * @return \Illuminate\Http\JsonResponse
     */
    private function getJsonFile(string $gameKey, string $fileName, string $subPath = '')
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $path = "public/games/{$gameKey}";
        if ($subPath) {
            $path .= "/{$subPath}";
        }
        $filePath = "{$path}/{$fileName}.json";

        if (!Storage::exists($filePath)) {
            return response()->json(['message' => 'Data not found.'], 404);
        }

        $content = Storage::get($filePath);
        return response()->json(json_decode($content, true));
    }

    public function api_game_atlas_get(string $gameKey)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        // モデルのメソッドを使用して全スプライトシート情報を取得
        $sheets = GameSpriteSheet::getSpriteSheetList();
        return response()->json($sheets);
    }

    public function api_game_characters_get(string $gameKey)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $keyword = [];
        $keyword['search_game_key'] = $gameKey;
        $game = GameList::getGameList(1, false, 1, $keyword)->first();
        if (!$game) return response()->json(['message' => 'Game not found.'], 404);

        // モデルのメソッドを使用してリストを取得
        $keyword = [];
        $keyword['search_game_id'] = $game->id;
        $characters = GameCharacter::getCharacterList(null, false, 1, $keyword);
        make_error_log($error_log, "Retrieved characters for game_key: {$gameKey}, count: " . $characters->count());

        return response()->json($characters->map(fn($char) => [
            'character_key' => $char->character_key,
            'name'          => $char->name,
            'type'          => $char->type,
            'motion_data'   => $char->motion_data, // 詳細データも含める
        ]));
    }

    public function api_game_character_get(string $gameKey, string $characterKey)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $keyword = [];
        $keyword['search_game_key'] = $gameKey;
        $game = GameList::getGameList(1, false, 1, $keyword)->first();
        if (!$game) return response()->json(['message' => 'Game not found.'], 404);

        $char = GameCharacter::where('game_id', $game->id)
            ->where('character_key', $characterKey)
            ->first();

        if (!$char) return response()->json(['message' => 'Character data not found.'], 404);

        return response()->json([
            'character_key' => $char->character_key,
            'name'          => $char->name,
            'type'          => $char->type,
            'motion_data'   => $char->motion_data,
        ]);
    }

    public function api_game_stages_get(string $gameKey)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        return $this->getJsonFile($gameKey, 'stages');
    }

    public function api_game_weapons_get(string $gameKey)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        return $this->getJsonFile($gameKey, 'weapons');
    }

    public function api_game_items_get(string $gameKey)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        return $this->getJsonFile($gameKey, 'items');
    }
}
