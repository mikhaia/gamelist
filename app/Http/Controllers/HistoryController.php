<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $games = Game::query()
            ->whereIn('game_list_id', $request->user()->gameLists()->select('id'))
            ->whereNotNull('completed_at')
            ->with('gameList')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get();

        $gamesByCompletionDate = $games->groupBy(
            fn (Game $game): string => $game->completed_at->format('Y-m-d'),
        );

        return view('history.index', compact('gamesByCompletionDate'));
    }
}
