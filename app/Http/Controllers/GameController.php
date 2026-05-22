<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    public function twinFacer()
    {
        return view('games.twin_facer');
    }

    public function asymmetryDungeon()
    {
        return view('games.asymmetry_dungeon');
    }
}
