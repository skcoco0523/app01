<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class privateController extends Controller
{
    public function half_birthday(Request $request)
    {
        
        $user = Auth::user();
        $isPrivateUser = $user?->isPrivateUser() ?? false;
        if ($isPrivateUser) {
            return view('private.half-birthday');
        }

        return redirect()->route('home');
    }
}