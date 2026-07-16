<?php

namespace App\Http\Controllers;

use App\Models\GameList;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicListController extends Controller
{
    public function show(Request $request, string $login, string $slug): View
    {
        $gameList = GameList::query()
            ->where('slug', $slug)
            ->where('is_public', true)
            ->whereHas('user', fn ($query) => $query->where('login', strtolower($login)))
            ->with('user')
            ->firstOrFail();

        $totalGames = $gameList->games()->count();
        $allowed = $gameList->availableStatusValues();
        $selectedStatuses = array_values(array_intersect((array) $request->query('status', []), $allowed));
        $sort = $request->query('sort') === 'completed_at' ? 'completed_at' : 'added_at';
        $games = $gameList->games()
            ->when($selectedStatuses !== [], fn ($query) => $query->whereIn('status', $selectedStatuses))
            ->sortedForList($sort)
            ->get();
        $gameList->setRelation('games', $games);

        return view('lists.public', [
            'gameList' => $gameList,
            'statuses' => $gameList->availableStatuses(),
            'selectedStatuses' => $selectedStatuses,
            'sort' => $sort,
            'totalGames' => $totalGames,
            'isFriend' => $request->user()?->isFriendsWith($gameList->user) ?? false,
        ]);
    }
}
