<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OledFace;
use App\Models\OledSpriteSheet; // OledPart から変更
use App\Services\OledBitmapCompilerService;
use Illuminate\Http\Request;

class OledFaceController extends Controller
{
    protected $compilerService;

    public function __construct(OledBitmapCompilerService $compilerService)
    {
        $this->compilerService = $compilerService;
    }

    public function index(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        
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
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        
        // 全てのスプライトシート（とそれに含まれるpart_data）を取得してエディタに渡す
        $sheets = OledSpriteSheet::all();

        return view('admin.admin_home', [
            'sheets' => $sheets,
        ]);
    }

    public function store(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_type' => 'required|string|unique:oled_faces,event_type',
            'interval_ms' => 'required|integer|min:20',
            'editor_json' => 'required|json',
            'point_cost' => 'integer|min:0',
        ]);

        $editorData = json_decode($validated['editor_json'], true);
        
        // JSONからの切り出しに対応した新しいコンパイラサービスを呼び出す前提
        $compiledBitmaps = $this->compilerService->compile($editorData);

        OledFace::create([
            'title' => $validated['title'],
            'event_type' => $validated['event_type'],
            'interval_ms' => $validated['interval_ms'],
            'editor_json' => $editorData, // 再編集用に保存
            'compiled_bitmaps' => $compiledBitmaps,
            'point_cost' => $validated['point_cost'] ?? 0,
        ]);

        return redirect()->route('admin.oled.index')->with('success', 'OLEDフェイスが正常に作成されました。');
    }

    public function edit(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $face = OledFace::findOrFail($id);
        $sheets = OledSpriteSheet::all();

        return view('admin.admin_home', [
            'face'   => $face,
            'sheets' => $sheets,
        ]);
    }

    public function update(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $face = OledFace::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'event_type' => 'required|string|unique:oled_faces,event_type,' . $id,
            'interval_ms' => 'required|integer|min:20',
            'editor_json' => 'required|json',
            'point_cost' => 'integer|min:0',
        ]);

        $editorData = json_decode($validated['editor_json'], true);
        $compiledBitmaps = $this->compilerService->compile($editorData);

        $face->update([
            'title' => $validated['title'],
            'event_type' => $validated['event_type'],
            'interval_ms' => $validated['interval_ms'],
            'editor_json' => $editorData,
            'compiled_bitmaps' => $compiledBitmaps,
            'point_cost' => $validated['point_cost'] ?? 0,
        ]);

        return redirect()->route('admin.oled.index')->with('success', 'OLEDフェイスが更新され、ビットマップが再コンパイルされました。');
    }

    public function destroy($id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        $face = OledFace::findOrFail($id);
        $face->delete();

        return redirect()->route('admin.oled.index')->with('success', 'OLEDフェイスを削除しました。');
    }
}