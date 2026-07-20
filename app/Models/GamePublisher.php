<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;

class GamePublisher
{
    /**
     * 🌟 ゲームに関連するすべての静的JSONデータをパブリッシュする (一括反映)
     */
    public static function publishAll($gameKey)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $game = GameList::where('game_key', $gameKey)
                ->with([
                    'characters' => fn($q) => $q->orderBy('sort_order', 'asc'),
                    'maps'       => fn($q) => $q->orderBy('id', 'asc'),
                    'stages'     => fn($q) => $q->with('map')->orderBy('number', 'asc'),
                    'weapons'    => fn($q) => $q->orderBy('sort_order', 'asc'),
                    'items'      => fn($q) => $q->orderBy('sort_order', 'asc'),
                ])->first();

            if (!$game) return ['success' => false, 'msg' => "ゲーム [{$gameKey}] が見つかりません。"];

            // 1. スプライトシート・マニフェストの反映 (個別詳細含む)
            self::publishSpriteSheets($gameKey);

            // 2. キャラクターデータの反映
            self::publishCharacters($game);

            // 3. マップ・ステージデータの反映
            self::publishStages($game);

            // 4. 武器・アイテムデータの反映
            self::publishEquipments($gameKey);

            return ['success' => true, 'msg' => "ゲーム [{$game->title}] の公開データをパブリッシュしました。"];
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => "パブリッシュ中にエラーが発生しました: " . $e->getMessage()];
        }
    }

    /**
     * 🌟 特定のスプライトシートのみをパブリッシュする (個別反映)
     */
    public static function publishSpriteSheet($gameKey, $filename)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        try {
            $baseDir = "public/games/{$gameKey}";
            $atlasDir = "{$baseDir}/atlas_sheets";
            $gridDir  = "{$baseDir}/grid_sheets";
            $atlasManifestPath = "{$baseDir}/atlas_sheets.json";
            $gridManifestPath  = "{$baseDir}/grid_sheets.json";

            if (!Storage::exists($baseDir)) Storage::makeDirectory($baseDir);
            if (!Storage::exists($atlasDir)) Storage::makeDirectory($atlasDir);
            if (!Storage::exists($gridDir))  Storage::makeDirectory($gridDir);

            $sheet = GameSpriteSheet::where('filename', $filename)->first();
            if (!$sheet) return ['success' => false, 'msg' => "スプライトシート [{$filename}] が見つかりません。"];

            // --- 1. pixel_data (ピクセルパーツ等) ---
            $atlasManifest = Storage::exists($atlasManifestPath) ? (json_decode(Storage::get($atlasManifestPath), true) ?? []) : [];
            if (!empty($sheet->pixel_data)) {
                // 詳細JSON保存
                Storage::put("{$atlasDir}/{$filename}.json", json_encode($sheet->pixel_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                // マニフェスト更新 (キーのみ、または基本情報のみに留める)
                $atlasManifest[$filename] = true; 
            } else {
                if (Storage::exists("{$atlasDir}/{$filename}.json")) Storage::delete("{$atlasDir}/{$filename}.json");
                unset($atlasManifest[$filename]);
            }
            Storage::put($atlasManifestPath, json_encode($atlasManifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            // --- 2. grid_data (グリッドパーツ) ---
            $gridManifest = Storage::exists($gridManifestPath) ? (json_decode(Storage::get($gridManifestPath), true) ?? []) : [];
            if (!empty($sheet->grid_data)) {
                // 詳細JSON保存
                Storage::put("{$gridDir}/{$filename}.json", json_encode($sheet->grid_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                // マニフェスト更新
                $gridManifest[$filename] = true;
            } else {
                if (Storage::exists("{$gridDir}/{$filename}.json")) Storage::delete("{$gridDir}/{$filename}.json");
                unset($gridManifest[$filename]);
            }
            Storage::put($gridManifestPath, json_encode($gridManifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return ['success' => true, 'msg' => "スプライトシート [{$filename}] を反映しました。"];
        } catch (\Exception $e) {
            if (function_exists('make_error_log')) {
                make_error_log($error_log, "Error Message: " . $e->getMessage());
            }
            return ['success' => false, 'msg' => "個別反映エラー: " . $e->getMessage()];
        }
    }

    /**
     * 内部メソッド: スプライトシート一括反映
     */
    private static function publishSpriteSheets($gameKey)
    {
        $game = GameList::where('game_key', $gameKey)->with(['characters', 'items'])->first();
        if (!$game) return;

        $usedFilenames = [];
        // キャラクター使用画像
        foreach ($game->characters as $char) {
            if (!empty($char->motion_data) && isset($char->motion_data['setup']['parts'])) {
                foreach ($char->motion_data['setup']['parts'] as $part) {
                    if (!empty($part['image'])) $usedFilenames[] = $part['image'];
                }
            }
        }
        // アイテム（グリッドパーツ）使用シート
        $itemSheetFilenames = GameSpriteSheet::whereIn('id', $game->items->pluck('sprite_sheet_id')->filter())->pluck('filename')->toArray();
        $usedFilenames = array_unique(array_merge($usedFilenames, $itemSheetFilenames));

        $sheets = GameSpriteSheet::whereIn('filename', $usedFilenames)->get();
        
        $baseDir = "public/games/{$gameKey}";
        $atlasDir = "{$baseDir}/atlas_sheets";
        $gridDir  = "{$baseDir}/grid_sheets";
        if (!Storage::exists($atlasDir)) Storage::makeDirectory($atlasDir);
        if (!Storage::exists($gridDir))  Storage::makeDirectory($gridDir);

        $atlasManifest = [];
        $gridManifest  = [];

        foreach ($sheets as $sheet) {
            if (!empty($sheet->pixel_data)) {
                $atlasManifest[$sheet->filename] = true;
                Storage::put("{$atlasDir}/{$sheet->filename}.json", json_encode($sheet->pixel_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
            if (!empty($sheet->grid_data)) {
                $gridManifest[$sheet->filename] = true;
                Storage::put("{$gridDir}/{$sheet->filename}.json", json_encode($sheet->grid_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
        }

        Storage::put("{$baseDir}/atlas_sheets.json", json_encode($atlasManifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        Storage::put("{$baseDir}/grid_sheets.json", json_encode($gridManifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // 🌟 不要な個別ファイルの削除
        $currentFilenames = array_keys(array_merge($atlasManifest, $gridManifest));
        self::cleanupUnusedFiles($atlasDir, $currentFilenames);
        self::cleanupUnusedFiles($gridDir, $currentFilenames);
    }

    /**
     * 内部メソッド: キャラクターデータ反映
     */
    private static function publishCharacters($game)
    {
        $baseDir = "public/games/{$game->game_key}";
        $charDir = "{$baseDir}/characters";
        if (!Storage::exists($charDir)) Storage::makeDirectory($charDir);
        
        $manifest = $game->characters->map(fn($char) => [
            'character_key'   => $char->character_key,
            'name'            => $char->name,
            'type'            => $char->type,
            'enable_flag'     => (bool)$char->enable_flag,
            'login_user_flag' => (bool)$char->login_user_flag,
            'admin_only_flag' => (bool)$char->admin_only_flag,
        ]);
        Storage::put("{$baseDir}/characters.json", json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        $activeKeys = [];
        foreach ($game->characters as $char) {
            $activeKeys[] = $char->character_key;
            Storage::put("{$charDir}/{$char->character_key}.json", json_encode([
                'character_key'   => $char->character_key,
                'name'            => $char->name,
                'type'            => $char->type,
                'enable_flag'     => (bool)$char->enable_flag,
                'login_user_flag' => (bool)$char->login_user_flag,
                'admin_only_flag' => (bool)$char->admin_only_flag,
                'motion_data'     => $char->motion_data,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }

        // 🌟 不要な個別ファイルの削除
        self::cleanupUnusedFiles($charDir, $activeKeys);
    }

    /**
     * 内部メソッド: マップ・ステージデータ反映
     */
    private static function publishStages($game)
    {
        $baseDir = "public/games/{$game->game_key}";
        $mapDir = "{$baseDir}/maps";
        $stageDir = "{$baseDir}/stages";
        if (!Storage::exists($mapDir)) Storage::makeDirectory($mapDir);
        if (!Storage::exists($stageDir)) Storage::makeDirectory($stageDir);
        
        // マップ一覧
        Storage::put("{$baseDir}/maps.json", json_encode($game->maps->map(fn($m) => [
            'map_key' => $m->map_key,
            'name'    => $m->name,
        ]), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // 個別マップ
        $activeMapKeys = [];
        foreach ($game->maps as $m) {
            $activeMapKeys[] = $m->map_key;
            Storage::put("{$mapDir}/{$m->map_key}.json", json_encode([
                'map_key'         => $m->map_key,
                'name'            => $m->name,
                'custom_settings' => $m->custom_settings,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        self::cleanupUnusedFiles($mapDir, $activeMapKeys);

        // ステージ一覧
        Storage::put("{$baseDir}/stages.json", json_encode($game->stages->map(fn($s) => [
            'number'          => $s->number,
            'name'            => $s->name,
            'type'            => $s->type,
            'enable_flag'     => (bool)$s->enable_flag,
            'login_user_flag' => (bool)$s->login_user_flag,
            'admin_only_flag' => (bool)$s->admin_only_flag,
        ]), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // 個別ステージ
        $activeStageKeys = [];
        foreach ($game->stages as $s) {
            $activeStageKeys[] = (string)$s->number;
            Storage::put("{$stageDir}/{$s->number}.json", json_encode([
                'number'          => $s->number,
                'name'            => $s->name,
                'type'            => $s->type,
                'enable_flag'     => (bool)$s->enable_flag,
                'login_user_flag' => (bool)$s->login_user_flag,
                'admin_only_flag' => (bool)$s->admin_only_flag,
                'map_data'        => $s->map ? $s->map->custom_settings : $s->custom_settings,
                'custom_settings' => $s->custom_settings,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        self::cleanupUnusedFiles($stageDir, $activeStageKeys);
    }

    /**
     * 🌟 武器・アイテムデータ反映
     */
    public static function publishEquipments($gameKey)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $game = GameList::where('game_key', $gameKey)
            ->with([
                'weapons' => fn($q) => $q->orderBy('sort_order', 'asc'),
                'items'   => fn($q) => $q->orderBy('sort_order', 'asc'),
            ])->first();

        if (!$game) return;

        $baseDir = "public/games/{$game->game_key}";
        $weaponDir = "{$baseDir}/weapons";
        $itemDir = "{$baseDir}/items";
        if (!Storage::exists($weaponDir)) Storage::makeDirectory($weaponDir);
        if (!Storage::exists($itemDir))   Storage::makeDirectory($itemDir);
        
        // 1. 武器一覧
        Storage::put("{$baseDir}/weapons.json", json_encode($game->weapons->map(fn($w) => [
            'weapon_key'  => $w->weapon_key,
            'name'         => $w->name,
            'type'         => $w->type,
            'enable_flag'  => (bool)$w->enable_flag,
        ]), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // 2. 武器詳細
        $activeWeaponKeys = [];
        foreach ($game->weapons as $w) {
            $activeWeaponKeys[] = $w->weapon_key;
            Storage::put("{$weaponDir}/{$w->weapon_key}.json", json_encode([
                'weapon_key'      => $w->weapon_key,
                'name'            => $w->name,
                'type'            => $w->type,
                'custom_settings' => $w->custom_settings,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        self::cleanupUnusedFiles($weaponDir, $activeWeaponKeys);

        // 3. アイテム一覧
        $file_name = "{$baseDir}/items.json";
        make_error_log($error_log, "Publishing items list, file: {$file_name}");
        Storage::put($file_name, json_encode($game->items->map(fn($i) => [
            'item_key'    => $i->item_key,
            'name'         => $i->name,
            'type'         => $i->type,
            'enable_flag'  => (bool)$i->enable_flag,
        ]), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // 4. アイテム詳細
        $activeItemKeys = [];
        foreach ($game->items as $i) {
            $activeItemKeys[] = $i->item_key;
            $customSettings = $i->custom_settings;
            $atlasFrameName = $i->atlas_frame; // アクセサ経由で取得

            if ($atlasFrameName && $i->sprite_sheet_id) {
                $sheet = GameSpriteSheet::find($i->sprite_sheet_id);
                if ($sheet && !empty($sheet->grid_data)) {
                    $frames = $sheet->grid_data['textures'][0]['frames'] ?? [];
                    $targetFrame = collect($frames)->firstWhere('name', $atlasFrameName);
                    if ($targetFrame && isset($targetFrame['frame'])) {
                        $customSettings['frame'] = $targetFrame['frame'];
                        $customSettings['image'] = $sheet->filename; // 画像名も便宜上含める
                    }
                }
            }

            $file_name = "{$itemDir}/{$i->item_key}.json";
            make_error_log($error_log, "Publishing item_detail: {$i->item_key}, file: {$file_name}");
            Storage::put($file_name, json_encode([
                'item_key'        => $i->item_key,
                'name'            => $i->name,
                'type'            => $i->type,
                'custom_settings' => $customSettings,
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        self::cleanupUnusedFiles($itemDir, $activeItemKeys);
    }

    /**
     * 🌟 フォルダ内の不要なJSONファイルをクリーンアップする
     */
    private static function cleanupUnusedFiles($directory, $activeKeys)
    {
        if (!Storage::exists($directory)) return;

        $existingFiles = Storage::files($directory);
        foreach ($existingFiles as $file) {
            $filename = pathinfo($file, PATHINFO_FILENAME);
            if (!in_array($filename, $activeKeys)) {
                Storage::delete($file);
            }
        }
    }
}
