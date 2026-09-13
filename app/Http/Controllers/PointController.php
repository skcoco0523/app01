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
    
    public function ad(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'ログインが必要です'
            ], 401);
        }

        // ポイント付与実行（10pt無償ポイント）
        $result = $user->add_po(10, false, '広告視聴');

        // JavaScriptへJSON形式で結果を返却
        return response()->json([
            'success'     => $result['success'],
            'total_point' => $result['total_point'] ?? 0,
            'message'     => $result['message'] ?? ''
        ]);
    }

}
