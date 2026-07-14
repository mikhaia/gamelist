<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Models\GameList;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GameListExportController extends Controller
{
    public function owner(Request $request, GameList $gameList): StreamedResponse
    {
        abort_unless($gameList->user_id === $request->user()->id, 403);

        return $this->download($request, $gameList);
    }

    public function public(Request $request, string $login, string $slug): StreamedResponse
    {
        $gameList = GameList::query()
            ->where('slug', $slug)
            ->where('is_public', true)
            ->whereHas('user', fn ($query) => $query->where('login', strtolower($login)))
            ->firstOrFail();

        return $this->download($request, $gameList);
    }

    private function download(Request $request, GameList $gameList): StreamedResponse
    {
        $statuses = $this->statuses($request);
        $games = $gameList->games()
            ->when($statuses !== [], fn ($query) => $query->whereIn('status', $statuses))
            ->get(['title']);

        return response()->streamDownload(function () use ($games): void {
            foreach ($games as $game) {
                echo '- '.$game->title.PHP_EOL;
            }
        }, $gameList->slug.'.txt', ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /** @return array<int, string> */
    private function statuses(Request $request): array
    {
        $allowed = array_column(GameStatus::cases(), 'value');

        return array_values(array_intersect((array) $request->query('status', []), $allowed));
    }
}
