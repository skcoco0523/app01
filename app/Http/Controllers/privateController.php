<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class privateController extends Controller
{
    public function half_birthday(Request $request)
    {
        $user_id = Auth::id();
        $auth_flag = false;
        //本番側
        if (config('app.env') === 'production'){
            if($user_id == 1) $auth_flag = true;
        }else{
            if($user_id == 1 || $user_id == 4) $auth_flag = true;
        }
        if ($auth_flag) {
            return view('private.half-birthday');
        }

        return redirect()->route('home');
    }
}