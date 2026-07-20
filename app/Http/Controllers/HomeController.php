<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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

        return view('user.home', compact('games'));

    }
    public function dashboard()
    {
        return view('dashboard');
    }

    
}
