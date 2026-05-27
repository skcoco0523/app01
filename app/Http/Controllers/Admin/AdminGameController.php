<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminGameController extends Controller
{
    /**
     * ゲーム全般設定画面
     */
    public function common_setting(Request $request)
    {
        return view('admin.admin_home', [
            'input' => $request->all(),
        ]);
    }

    /**
     * スプライトシート管理画面
     */
    public function sprite_sheet(Request $request)
    {
        $directory = public_path('storage/sprite_sheet');
        $allFiles = File::exists($directory) ? File::files($directory) : [];
        
        // PNG画像ファイルだけを抽出して一覧化
        $images = [];
        foreach ($allFiles as $file) {
            if (strtolower($file->getExtension()) === 'png') {
                $images[] = $file->getFilename();
            }
        }

        // 検索フィルタ処理
        $search = $request->input('search');
        if (!empty($search)) {
            $images = array_filter($images, function($filename) use ($search) {
                return str_contains($filename, $search);
            });
        }

        // 現在編集中のターゲットファイル
        $activeFile = $request->input('file');
        $atlasContent = '';
        $motionContent = '';

        if ($activeFile) {
            $baseName = pathinfo($activeFile, PATHINFO_FILENAME);
            
            // _atlas.json と _motion.json のパスを解決
            $atlasPath = public_path("storage/sprite_sheet/{$baseName}_atlas.json");
            $motionPath = public_path("storage/sprite_sheet/{$baseName}_motion.json");

            if (File::exists($atlasPath)) {
                $atlasContent = File::get($atlasPath);
            }
            if (File::exists($motionPath)) {
                $motionContent = File::get($motionPath);
            }
        }

        return view('admin.admin_home', [
            'images' => $images,
            'activeFile' => $activeFile,
            'atlasContent' => $atlasContent,
            'motionContent' => $motionContent,
            'input' => $request->all(),
        ]);
    }

    /**
     * JSONデータの更新（アトラスとモーションの同時対応）
     */
    public function sprite_sheet_update(Request $request)
    {
        $filename = $request->input('filename');
        $atlasContent = $request->input('atlas_content');
        $motionContent = $request->input('motion_content');

        if (!$filename) {
            return redirect()->back()->with('msg', 'ファイルが選択されていません。');
        }

        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $atlasPath = public_path("storage/sprite_sheet/{$baseName}_atlas.json");
        $motionPath = public_path("storage/sprite_sheet/{$baseName}_motion.json");

        try {
            // アトラスJSON形式のチェック
            if (!empty($atlasContent) && json_decode($atlasContent) === null) {
                return redirect()->back()->with('msg', '保存失敗：アトラスJSONの形式が正しくありません。');
            }
            // モーションJSON形式のチェック
            if (!empty($motionContent) && json_decode($motionContent) === null) {
                return redirect()->back()->with('msg', '保存失敗：モーションJSONの形式が正しくありません。');
            }

            // ファイルの書き込み
            if (!empty($atlasContent)) File::put($atlasPath, $atlasContent);
            if (!empty($motionContent)) File::put($motionPath, $motionContent);

            return redirect()->route('admin.game.sprite_sheet', [
                'file' => $filename,
                'mode' => $request->input('mode')
            ])->with('msg', 'JSON設定を保存しました。');
        } catch (\Exception $e) {
            return redirect()->back()->with('msg', 'エラーが発生しました: ' . $e->getMessage());
        }
    }
}