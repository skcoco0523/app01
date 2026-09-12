<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use App\Models\CommonConfig;


class PointController extends Controller
{
    //ポイント購入ページ
    public function buy(Request $request)
    {
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        $profile = User::getProfile(Auth::id());
        $common_conf_names = [
            'po_pack1', 'po_pack2', 'po_pack3', 'po_pack4', 'po_pack5', 'po_pack6'
        ];
        $packs = CommonConfig::getValues($common_conf_names);
        //dd($packs);

        $msg = "";
            
        return view('point.buy', compact('profile','packs', 'msg'));

    }
    //ポイント購入処理
    public function checkout(Request $request)
    {
        // 選択されたパックIDの受け取り（例: p500, p1000など）
        $packId = $request->input('pack_id');

        // TODO: Stripe等の決済処理・DB更新ロジックをここに実装
        
        return back()->with('message', '選択されたパック: ' . $packId);
    }
    /**
     * 広告視聴・無料ポイント獲得処理
     */
    public function ad()
    {
        // TODO: 広告動画視聴画面の表示、または10pt付与ロジックの実装
        
        return back()->with('message', '広告を視聴して10pt獲得しました');
    }

}
