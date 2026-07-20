<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\GameList;
use App\Models\GameSpriteSheet;
use App\Models\GamePublisher;

class AdminGameAssetController extends Controller
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
    // スプライトシート管理 (game/sprite-sheet)
    //==================================================================================
    public function sprite_sheet_index(Request $request)
    {
        $games = GameList::getGameList();
        $gameKey = $request->input('game_key', 'twin_facer');

        $keyword = [];
        if ($request->filled('tag'))    $keyword['search_tag'] = $request->input('tag');
        if ($request->filled('search')) $keyword['search_keyword'] = $request->input('search');
        $keyword['admin_flag'] = true;

        $assets = GameSpriteSheet::getSpriteSheetList(null, false, 1, $keyword);

        $activeFile = $request->input('file');
        $activeFileCategory = 'character';
        $activeSpriteSheet = null;

        if ($activeFile) {
            $keyword = [];
            $keyword['search_filename'] = $activeFile;
            $keyword['admin_flag'] = true;
            $activeSpriteSheet = GameSpriteSheet::getSpriteSheetList(1, false, 1, $keyword)->first();
            if ($activeSpriteSheet) {
                $activeFileCategory = $activeSpriteSheet->category;
            }
        }

        return view('admin.admin_home', [
            'games'              => $games,
            'assets'             => $assets,
            'activeFile'         => $activeFile,
            'activeFileCategory' => $activeFileCategory,
            'activeSpriteSheet'  => $activeSpriteSheet,
            'categories'         => $this->assetCategories,
            'gameKey'            => $gameKey,
            'parts_mode'         => null, // 画像管理モード
        ]);
    }

    public function pixel_parts_index(Request $request)
    {
        return $this->common_parts_index($request, 'pixel');
    }

    public function grid_parts_index(Request $request)
    {
        return $this->common_parts_index($request, 'grid');
    }

    private function common_parts_index(Request $request, $mode)
    {
        
        $input = $request->all();
        $msg              = get_proc_data($input,"msg");

        $games = GameList::getGameList();
        $gameKey = $request->input('game_key', 'twin_facer');

        $keyword = [];
        if ($request->filled('tag'))    $keyword['search_tag'] = $request->input('tag');
        if ($request->filled('search')) $keyword['search_keyword'] = $request->input('search');
        $keyword['admin_flag'] = true;

        $assets = GameSpriteSheet::getSpriteSheetList(null, false, 1, $keyword);

        $activeFile = $request->input('file');
        $activeFileCategory = 'character';
        $activeSpriteSheet = null;

        
        $atlasContent = '';
        $definedParts = [];
        if ($activeFile) {
            $keyword = [];
            $keyword['search_filename'] = $activeFile;
            $keyword['admin_flag'] = true;
            $activeSpriteSheet = GameSpriteSheet::getSpriteSheetList(1, false, 1, $keyword)->first();
            if ($activeSpriteSheet) {
                $activeFileCategory = $activeSpriteSheet->category;
                
                // 🌟 モードに応じて読み込むカラムを切り替え
                $currentData = ($mode === 'grid') ? $activeSpriteSheet->grid_data : $activeSpriteSheet->pixel_data;
                
                // JSON文字列としてビューに渡す（エディタ用）
                $atlasContent = json_encode($currentData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                
                // 配列としてビューに渡す（一覧表示用）
                $definedParts = $currentData['textures'][0]['frames'] ?? [];
            }
        }

        // 素材パレット用のデータを準備
        $images = $assets->pluck('filename')->toArray();

        return view('admin.admin_home', [
            'games'                 => $games,
            'definedParts'          => $definedParts,
            'assets'                => $assets,
            'images'                => $images, // パレット用
            'activeFile'            => $activeFile,
            'activeSpriteSheet'     => $activeSpriteSheet,
            'activeFileCategory'    => $activeFileCategory,
            'atlasContent'          => $atlasContent,
            'categories'            => $this->assetCategories,
            'gameKey'               => $gameKey,
            'motionContent'         => '',
            'parts_mode'            => $mode,
            'msg'                   => $msg,
        ]);
    }

    public function sprite_sheet_upload(Request $request)
    {
        $request->validate([
            'category'    => 'required|string',
            'sprite_file' => 'required|image|mimes:png|max:5120',
        ]);

        $category = $request->input('category');
        if (!array_key_exists($category, $this->assetCategories)) {
            return redirect()->back()->with('msg', '⚠️エラー: 不正なカテゴリが指定されました。');
        }

        if ($request->hasFile('sprite_file')) {
            $file = $request->file('sprite_file');
            $filename = basename($file->getClientOriginalName());
            if (!preg_match('/^[\w\-]+\.png$/i', $filename)) {
                return redirect()->back()->with('msg', '⚠️エラー: ファイル名に使用できない文字が含まれています。');
            }
            $subFolder = "public/sprite_sheet/{$category}";
            if (!Storage::exists($subFolder)) {
                Storage::makeDirectory($subFolder);
            }

            $file->storeAs($subFolder, $filename);
            list($width, $height) = getimagesize($file->getRealPath());
            //画像アップロード時のデフォルト定義
            $defaultData = [
                'textures' => [[ 'image' => $filename, 'size' => ['w' => $width, 'h' => $height], 'frames' => [] ]]
            ];
            $atlasData = $defaultData;
            $gridData = $defaultData;

            GameSpriteSheet::createSpriteSheet($filename, $category, $atlasData, $gridData);

            return redirect()->route('admin.game.sprite_sheet.index', ['file' => $filename])
                ->with('msg', "🟢 画像倉庫に「{$filename}」を追加し、パーツ定義枠を生成しました！");
        }

        return redirect()->back()->with('msg', '⚠️エラー: アップアップロードに失敗しました。');
    }

    public function sprite_sheet_update(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        make_error_log($error_log, "sprite_sheet_update called with mode: " . $request->input('mode') . ", parts_mode: " . $request->input('parts_mode'));

        $input = $request->all();
        $filename                       = get_proc_data($input,"filename");
        $parts_mode                     = get_proc_data($input,"parts_mode");
        $input['type']                  = get_proc_data($input,"device_type");
        $input['ver']                   = get_proc_data($input,"device_ver");
        $input['name']                  = get_proc_data($input,"name");

        $msg=null;
        if(!$filename)          $msg =  "画像ファイルが指定されていません。";
        if(!$parts_mode)        $msg =  "パーツモードが指定されていません。";
        
        if($msg==null){
            $parts_mode = $request->input('parts_mode');    // 'pixel' or 'grid'
            // アトラス座標保存
            $atlasContent = $request->input('atlas_content');
            $atlasData = !empty($atlasContent) ? json_decode($atlasContent, true) : null;
            if ($atlasContent && $atlasData === null) {
                $msg = "形式エラー：アトラスJSON";
            }else{
                // 🌟 sizeプロパティが欠落している場合は物理ファイルから取得して差し込む
                if ($atlasData && isset($atlasData['textures'][0]) && !isset($atlasData['textures'][0]['size'])) {
                    $sheet = GameSpriteSheet::where('filename', $filename)->first();
                    if ($sheet) {
                        $path = Storage::path("public/sprite_sheet/{$sheet->category}/{$filename}");
                        if (file_exists($path)) {
                            list($width, $height) = getimagesize($path);
                            $atlasData['textures'][0]['size'] = ['w' => $width, 'h' => $height];
                            make_error_log($error_log, "Size injected for {$filename}: {$width}x{$height}");
                        }
                    }
                }
                // 定義したjsonをDBに保存する
                $result = GameSpriteSheet::chgSpriteSheet($filename, $atlasData, $parts_mode);
                $msg = $result['msg'];
            }
        }

        if($parts_mode === 'grid'){
            return redirect()->route('admin.game.grid_parts.index', ['input' => $input, 'file' => $filename, 'msg' => $msg]);
        }elseif($parts_mode === 'pixel'){
            return redirect()->route('admin.game.pixel_parts.index', ['input' => $input, 'file' => $filename, 'msg' => $msg]);
        }else{
            return redirect()->route('admin.game.sprite_sheet.index', ['input' => $input, 'file' => $filename, 'msg' => $msg]);
        }
    }

    public function sprite_sheet_rename(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $input          = $request->all();
        $filename       = get_proc_data($input,"filename");
        $new_filename   = get_proc_data($input,"new_filename");
        
        // 名前変更
        $msg = null;
        if ($filename && $new_filename) {
            $newName = basename($new_filename);
            if (!preg_match('/^[\w\-]+\.png$/i', $newName)) {
                $msg = "⚠️エラー: ファイル名に使用できない文字が含まれています（半角英数字、ハイフン、アンダースコアのみ、拡張子.png必須）。";

            }else{
                $result = GameSpriteSheet::renameSpriteSheet($filename, $newName);
                $msg = $result['msg'];
                // 物理ファイルのリネーム
                if ($result['success']) {
                    $sheet = $result['sheet'];
                    $oldPath = "public/sprite_sheet/{$sheet->category}/{$filename}";
                    $newPath = "public/sprite_sheet/{$sheet->category}/{$newName}";
                    if (Storage::exists($oldPath)) {
                        Storage::move($oldPath, $newPath);
                        make_error_log($error_log, "Physical file renamed: {$oldPath} -> {$newPath}");
                    }
                    $filename = $newName;

                }
            }
        }
        
        return redirect()->route('admin.game.sprite_sheet.index', ['file' => $filename, 'msg' => $msg]);
    }


    public function sprite_sheet_destroy(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $input          = $request->all();
        $filename       = get_proc_data($input,"filename");
        $result         = GameSpriteSheet::delSpriteSheet($filename);

        $msg = null;
        if ($result['success']) {
            $physicalPath = "public/sprite_sheet/{$result['sheet']->category}/{$filename}";
            if (Storage::exists($physicalPath)) {
                Storage::delete($physicalPath);
                make_error_log($error_log, "Physical file deleted: {$physicalPath}");
            }
            $msg = "🔴 画像「{$filename}」を完全削除しました。";
            // 削除後は選択解除
            $filename = null;
        }else{
            $msg = $result['msg'] ?? '削除中にエラーが発生しました。';
        }
        return redirect()->route('admin.game.sprite_sheet.index', ['file' => $filename, 'msg' => $msg]);
    }
}
