<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OledFace;
use App\Models\OledSpriteSheet;
use Illuminate\Http\Request;

class OledFaceController extends Controller
{
    public function index(Request $request)
    {
        $input = $request->all();
        $query = OledFace::query();

        if (!empty($input['search_title'])) {
            $query->where('title', 'like', '%' . $input['search_title'] . '%');
        }
        if (!empty($input['search_event_type'])) {
            $query->where('event_type', 'like', '%' . $input['search_event_type'] . '%');
        }

        $oled_faces = $query->orderBy('id', 'desc')->paginate(10);
        $msg = $oled_faces->isEmpty() ? "登録されたOLEDフェイスはありません。" : null;

        return view('admin.admin_home', [
            'faces'      => $oled_faces,
            'oled_faces' => $oled_faces,
            'input'      => $input,
            'msg'        => $msg,
        ]);
    }

    public function create(Request $request)
    {
        $sheets = OledSpriteSheet::all();
        $allParts = $this->getAllPartsFromSheets($sheets);

        return view('admin.admin_home', [
            'sheets'   => $sheets,
            'allParts' => $allParts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_type' => 'required|string|unique:oled_faces,event_type',
            'interval_ms' => 'required|integer|min:20',
            'editor_json' => 'required|json',
            'point_cost' => 'integer|min:0',
        ]);

        $editorData = json_decode($validated['editor_json'], true);

        // 🌟 ESP32配信用に最適化したデータ構造を生成
        $compiledDeviceJson = $this->buildCompiledDeviceJson($editorData);

        OledFace::create([
            'title'                => $validated['title'],
            'event_type'           => $validated['event_type'],
            'interval_ms'          => $validated['interval_ms'],
            'editor_json'          => $editorData,         // Webエディタ再編集用
            'compiled_device_json' => $compiledDeviceJson, // 🌟 ESP32配信用（パーツ1bpp化＋軽量配列）
            'point_cost'           => $validated['point_cost'] ?? 0,
        ]);

        return redirect()->route('admin.oled.index')->with('success', 'OLEDフェイスが正常に作成されました。');
    }

    public function edit(Request $request, $id)
    {
        $face = OledFace::findOrFail($id);
        $sheets = OledSpriteSheet::all();
        $allParts = $this->getAllPartsFromSheets($sheets);

        return view('admin.admin_home', [
            'face'     => $face,
            'sheets'   => $sheets,
            'allParts' => $allParts,
        ]);
    }

    public function update(Request $request, $id)
    {
        $face = OledFace::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_type' => 'required|string|unique:oled_faces,event_type,' . $id,
            'interval_ms' => 'required|integer|min:20',
            'editor_json' => 'required|json',
            'point_cost' => 'integer|min:0',
        ]);

        $editorData = json_decode($validated['editor_json'], true);

        // 🌟 ESP32配信用に最適化したデータ構造を生成
        $compiledDeviceJson = $this->buildCompiledDeviceJson($editorData);

        $face->update([
            'title'                => $validated['title'],
            'event_type'           => $validated['event_type'],
            'interval_ms'          => $validated['interval_ms'],
            'editor_json'          => $editorData,
            'compiled_device_json' => $compiledDeviceJson, // 🌟 ESP32配信用データ更新
            'point_cost'           => $validated['point_cost'] ?? 0,
        ]);

        return redirect()->route('admin.oled.index')->with('success', 'OLEDフェイスが更新されました。');
    }

    public function destroy($id)
    {
        $face = OledFace::findOrFail($id);
        $face->delete();

        return redirect()->route('admin.oled.index')->with('success', 'OLEDフェイスを削除しました。');
    }

    private function getAllPartsFromSheets($sheets)
    {
        $allParts = [];
        foreach ($sheets as $sheet) {
            $sheetParts = $sheet->part_data ?? [];
            foreach ($sheetParts as $p) {
                $p['sheet_id'] = $sheet->id;
                $p['sheet_file_path'] = $sheet->file_path;
                $allParts[] = $p;
            }
        }
        return $allParts;
    }

    /**
     * 🌟 editor_json から ESP32配信用に最適化した compiled_device_json データ構造をビルド
     */
    private function buildCompiledDeviceJson(array $editorData): array
    {
        $partsTable = [];
        $formattedFrames = [];
        $partMap = []; // UUIDとエイリアス(P0, P1...)のマッピング

        $frames = $editorData['frames'] ?? [];

        foreach ($frames as $frame) {
            $layers = $frame['layers'] ?? [];

            // 1. zIndex 順にソート（ESP32側での描画順を保証）
            usort($layers, fn($a, $b) => ($a['zIndex'] ?? 0) <=> ($b['zIndex'] ?? 0));

            $frameLayers = [];
            foreach ($layers as $layer) {
                $partId = $layer['part_id'] ?? null;
                if (!$partId) continue;

                // 初見のパーツなら1bppモノクロHEX化してパーツテーブルに登録
                if (!isset($partMap[$partId])) {
                    $alias = 'P' . count($partMap);
                    $partMap[$partId] = $alias;

                    // パーツの画像からモノクロビットマップデータ（HEX文字列）を生成
                    $hexBmp = $this->convertLayerTo1bppHex($layer);

                    $partsTable[$alias] = [
                        'w'   => (int)($layer['w'] ?? $layer['src_width'] ?? 16),
                        'h'   => (int)($layer['h'] ?? $layer['src_height'] ?? 16),
                        'bmp' => $hexBmp
                    ];
                }

                // 2. コマ内の配置データ（不要なプロパティを削ぎ落として最小化）
                $frameLayers[] = [
                    'id' => $partMap[$partId],
                    'x'  => (int)($layer['x'] ?? 0),
                    'y'  => (int)($layer['y'] ?? 0),
                ];
            }
            $formattedFrames[] = $frameLayers;
        }

        return [
            'interval' => (int)($editorData['interval_ms'] ?? 150),
            'parts'    => $partsTable,
            'frames'   => $formattedFrames,
        ];
    }

    /**
     * 🌟 レイヤー情報（スプライトシート画像）から該当パーツ領域を切り出し1bppモノクロHEX化
     */
    private function convertLayerTo1bppHex(array $layer): string
    {
        $filePath = $layer['sheet_file_path'] ?? null;
        if (!$filePath || !file_exists(public_path($filePath))) {
            return '';
        }

        $img = @imagecreatefrompng(public_path($filePath));
        if (!$img) return '';

        $srcX = (int)($layer['src_x'] ?? 0);
        $srcY = (int)($layer['src_y'] ?? 0);
        $w = (int)($layer['w'] ?? $layer['src_width'] ?? 16);
        $h = (int)($layer['h'] ?? $layer['src_height'] ?? 16);

        // 1bpp バイト配列の生成（横方向ビット詰め）
        $bytesPerLine = (int)ceil($w / 8);
        $bytes = array_fill(0, $bytesPerLine * $h, 0);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $pixelX = $srcX + $x;
                $pixelY = $srcY + $y;

                if ($pixelX >= imagesx($img) || $pixelY >= imagesy($img)) continue;

                $rgba = imagecolorat($img, $pixelX, $pixelY);
                $alpha = ($rgba >> 24) & 0x7F;

                // アルファ値（不透明度）と輝度判定（白ピクセル判定）
                if ($alpha < 64) {
                    $r = ($rgba >> 16) & 0xFF;
                    $g = ($rgba >> 8) & 0xFF;
                    $b = $rgba & 0xFF;
                    $brightness = ($r * 299 + $g * 587 + $b * 114) / 1000;

                    if ($brightness > 128) {
                        $byteIdx = ($y * $bytesPerLine) + intdiv($x, 8);
                        $bitIdx = 7 - ($x % 8);
                        $bytes[$byteIdx] |= (1 << $bitIdx);
                    }
                }
            }
        }
        imagedestroy($img);

        return strtoupper(implode('', array_map(fn($b) => str_pad(dechex($b), 2, '0', STR_PAD_LEFT), $bytes)));
    }
}