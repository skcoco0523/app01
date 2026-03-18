<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;

class RouletteController extends Controller
{
    //ルーレットページ
    public function show(Request $request)
    {
        //リダイレクトの場合、inputを取得
        if($request->input('input')!==null)     $input = request('input');
        else                                    $input = $request->all();

        $input['type']              = get_proc_data($input,"type");
        $input['message']           = get_proc_data($input,"message");
        $input['page']              = get_proc_data($input,"page");

        $msg = "";
        //$user_request = UserRequest::getRequest_list(10,true,$input['page'],['login_id' => $user_id]);  //件数,ﾍﾟｰｼﾞｬｰ,ｶﾚﾝﾄﾍﾟｰｼﾞ,ｷｰﾜｰﾄﾞ
            
        return view('roulette.show', compact('input', 'msg'));

    }

}
