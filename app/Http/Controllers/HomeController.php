<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Home;
use App\Models\GameList;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //ホームはゲストも表示可能に
        //$this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        //利用可能ゲーム
        $keyword = [];
        $keyword['search_dummy'] = true;
        $games = GameList::getGameList(99, false, 1, $keyword);

        $user = Auth::user();
        $isPrivateUser = $user?->isPrivateUser() ?? false;

        return view('user.home', compact('games', 'isPrivateUser'));

    }
    public function dashboard()
    {
        return view('dashboard');
    }

    
}
