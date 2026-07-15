<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function __invoke(Request $request): View
    {
        $games = Game::query()
            ->whereIn('game_list_id', $request->user()->gameLists()->select('id'))
            ->where(fn ($query) => $query->whereNotNull('started_at')->orWhereNotNull('completed_at'))
            ->with('gameList')
            ->get();

        $events = $games
            ->flatMap(function (Game $game): array {
                $events = [];

                if ($game->started_at !== null) {
                    $events[] = ['type' => 'started', 'date' => $game->started_at->copy(), 'game' => $game];
                }

                if ($game->completed_at !== null) {
                    $events[] = ['type' => 'completed', 'date' => $game->completed_at->copy(), 'game' => $game];
                }

                return $events;
            })
            ->sortByDesc(fn (array $event): int => $event['date']->getTimestamp() + ($event['type'] === 'completed' ? 1 : 0))
            ->values();

        /** @var Collection<string, Collection<int, array{type: string, date: mixed, game: Game}>> $eventsByDate */
        $eventsByDate = $events->groupBy(fn (array $event): string => $event['date']->format('Y-m-d'));

        return view('history.index', compact('eventsByDate'));
    }
}
