<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GuideController extends Controller
{
    /**
     * ガイド・解説記事一覧表示
     */
    public function index(Request $request)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        $input['page'] = get_proc_data($input, "page");

        // ※将来的にDB（Guideモデル等）から記事一覧を取得する場合はここを調整
        return view('portal.guide.index', compact('input'));
    }

    /**
     * ガイド・解説記事詳細表示
     */
    public function show(Request $request, $id)
    {
        $error_log = class_basename(__CLASS__) . '_' . __FUNCTION__ . ".log";
        
        // ※記事IDに応じた詳細データを取得してビューへ渡す
        return view('portal.guide.show', compact('id'));
    }
}