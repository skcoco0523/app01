<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OledSpriteSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OledPartController extends Controller
{
    public function index(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        
        $sheets = OledSpriteSheet::all();
        $selectedSheetId = $request->input('sheet_id', $sheets->first()?->id);
        $selectedSheet = $sheets->firstWhere('id', $selectedSheetId);
        
        // 選択されたシートのJSONからパーツ配列を取得
        $parts = $selectedSheet ? ($selectedSheet->part_data ?? []) : [];

        // 全シートの全パーツをフラットな配列として取得（エディタ等で全パーツ一覧が必要な場合）
        $allParts = [];
        foreach ($sheets as $sheet) {
            $sheetParts = $sheet->part_data ?? [];
            foreach ($sheetParts as $p) {
                // シートIDを付与してどのシート由来か分かるようにしておく
                $p['sheet_id'] = $sheet->id;
                $allParts[] = $p;
            }
        }

        return view('admin.admin_home', [
            'sheets'          => $sheets,
            'selectedSheet'   => $selectedSheet,
            'parts'           => $parts,
            'allParts'        => $allParts,
        ]);
    }

    public function uploadSheet(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        $request->validate([
            'name'  => 'required|string|max:255',
            'image' => 'required|image|mimes:png,jpg,jpeg|max:2048',
        ]);

        $file = $request->file('image');
        $filename = uniqid('sheet_') . '.' . $file->getClientOriginalExtension();
        $destinationPath = public_path('storage/oled_sprite_sheets');
        
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $file->move($destinationPath, $filename);

        $sheet = OledSpriteSheet::create([
            'name'      => $request->input('name'),
            'file_path' => 'storage/oled_sprite_sheets/' . $filename,
            'part_data' => [], // 初期値は空の配列
        ]);

        return redirect()->route('admin.oled.parts.index', ['sheet_id' => $sheet->id])
            ->with('success', 'スプライトシートをアップロードしました。');
    }

    public function storePart(Request $request)
    {
        
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        // リクエストフォームから sheet_id (または oled_sprite_sheet_id) を取得
        $sheet_id = $request->input('oled_sprite_sheet_id');
        $sheet = OledSpriteSheet::findOrFail($sheet_id);
        make_error_log($error_log,"sheet_id:".$sheet_id);

        // 送信されてきたJSON文字列を配列にデコード
        $rawParts = $request->input('part_data');
        make_error_log($error_log,"rawParts:".$rawParts);
        $partsData = is_string($rawParts) ? json_decode($rawParts, true) : ($rawParts ?? []);

        // 各パーツにIDがなければ付与する
        foreach ($partsData as &$part) {
            if (empty($part['id'])) {
                $part['id'] = Str::uuid()->toString();
            }
            // 数値型にキャスト
            $part['src_x']      = (int)($part['src_x'] ?? 0);
            $part['src_y']      = (int)($part['src_y'] ?? 0);
            $part['src_width']  = (int)($part['src_width'] ?? 1);
            $part['src_height'] = (int)($part['src_height'] ?? 1);
        }

        // スプライトシートの part_data を一括上書き保存
        $sheet->part_data = $partsData;
        $sheet->save();

        return back()->with('success', '全てのパーツ情報を一括保存しました。');
    }

    public function updatePart(Request $request, $sheetId, $partId)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";

        $validated = $request->validate([
            'name'                 => 'required|string|max:255',
            'category'             => 'required|string',
            'src_x'                => 'required|integer|min:0',
            'src_y'                => 'required|integer|min:0',
            'src_width'            => 'required|integer|min:1',
            'src_height'           => 'required|integer|min:1',
        ]);

        $sheet = OledSpriteSheet::findOrFail($sheetId);
        $partsData = is_array($sheet->part_data) ? $sheet->part_data : [];
        $updated = false;

        // 該当のpartIdを探して更新
        foreach ($partsData as $index => $part) {
            if (isset($part['id']) && $part['id'] === $partId) {
                $partsData[$index] = array_merge($part, [
                    'name'       => $validated['name'],
                    'category'   => $validated['category'],
                    'src_x'      => $validated['src_x'],
                    'src_y'      => $validated['src_y'],
                    'src_width'  => $validated['src_width'],
                    'src_height' => $validated['src_height'],
                ]);
                $updated = true;
                break;
            }
        }

        if ($updated) {
            $sheet->part_data = $partsData;
            $sheet->save();
            return redirect()->route('admin.oled.parts.index', ['sheet_id' => $sheet->id])
                ->with('success', 'パーツ座標を更新しました。');
        }

        return back()->withErrors(['part' => '対象のパーツが見つかりません。']);
    }

    public function destroyPart(Request $request, $sheetId, $partId)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        
        $sheet = OledSpriteSheet::findOrFail($sheetId);
        $partsData = is_array($sheet->part_data) ? $sheet->part_data : [];
        
        // 該当のpartIdを除外した配列を作成
        $newPartsData = array_filter($partsData, function($part) use ($partId) {
            return isset($part['id']) && $part['id'] !== $partId;
        });

        // インデックスを詰める
        $sheet->part_data = array_values($newPartsData);
        $sheet->save();

        return redirect()->route('admin.oled.parts.index', ['sheet_id' => $sheet->id])
            ->with('success', 'パーツを削除しました。');
    }

    public function destroySheet($id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $sheet = OledSpriteSheet::findOrFail($id);

        // 紐づく画像ファイル(スプライトシート本体)の削除
        $sheetFullPath = public_path($sheet->file_path);
        if (file_exists($sheetFullPath)) {
            @unlink($sheetFullPath);
        }
        
        // テーブルレコードの削除（part_dataも一緒に消える）
        $sheet->delete();

        return redirect()->route('admin.oled.parts.index')->with('success', 'スプライトシートを削除しました。');
    }
}