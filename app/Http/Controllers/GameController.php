<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    public function play($gameKey)
    {
        $game = \App\Models\GameList::where('game_key', $gameKey)->firstOrFail();
        return view('games.play', ['game' => $game, 'gameKey' => $gameKey]);
    }
}
