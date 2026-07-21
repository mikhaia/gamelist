<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        return $this->history($request, $request->user());
    }

    public function show(Request $request, string $login): View
    {
        $profile = User::query()
            ->where('login', strtolower($login))
            ->firstOrFail();

        return $this->history($request, $profile);
    }

    private function history(Request $request, User $profile): View
    {
        $isOwner = $request->user()?->is($profile) ?? false;
        $games = Game::query()
            ->whereHas('gameList', fn ($query) => $query
                ->where('user_id', $profile->getKey())
                ->when(! $isOwner, fn ($gameLists) => $gameLists->where('is_public', true)))
            ->whereNotNull('completed_at')
            ->with('gameList')
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get();

        $gamesByCompletionDate = $games->groupBy(
            fn (Game $game): string => $game->completed_at->format('Y-m-d'),
        );

        return view('history.index', compact('gamesByCompletionDate', 'profile', 'isOwner'));
    }
}
