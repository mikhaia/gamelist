<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Models\CatalogGame;
use App\Models\Game;
use App\Models\GameScreenshot;
use App\Services\RawgCatalogEnricher;
use App\Services\ReviewMarkdown;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GamePageController extends Controller
{
    public function __construct(
        private readonly ReviewMarkdown $markdown,
        private readonly RawgCatalogEnricher $rawg,
    ) {}

    public function __invoke(Request $request, CatalogGame $catalogGame): View
    {
        $catalogGame = $this->rawg->enrich($catalogGame);

        $statusTotals = $catalogGame->games()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $statusCounts = collect(GameStatus::cases())->mapWithKeys(
            fn (GameStatus $status): array => [$status->value => (int) ($statusTotals[$status->value] ?? 0)],
        );
        $coverGame = $catalogGame->games()->whereNotNull('cover_path')->latest()->first();
        $reviews = $catalogGame->reviews()
            ->with('user')
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->latest()
            ->paginate(10);
        $reviews->getCollection()->each(
            fn ($review) => $review->setAttribute('rendered_body', $this->markdown->render($review->body)),
        );

        $user = $request->user();
        $userReview = $user
            ? $catalogGame->reviews()->where('user_id', $user->id)->first()
            : null;
        $userLists = $user?->gameLists()->latest()->get() ?? collect();
        $addedListIds = $user
            ? Game::query()
                ->where('catalog_game_id', $catalogGame->id)
                ->whereHas('gameList', fn ($query) => $query->where('user_id', $user->id))
                ->pluck('game_list_id')
                ->unique()
                ->values()
            : collect();
        $userScreenshots = GameScreenshot::query()
            ->whereHas('game', function ($query) use ($catalogGame, $user): void {
                $query->where('catalog_game_id', $catalogGame->id)
                    ->whereHas('gameList', function ($query) use ($user): void {
                        $query->where('is_public', true)
                            ->when($user, fn ($query) => $query->orWhere('user_id', $user->id));
                    });
            })
            ->with('game.gameList.user')
            ->latest()
            ->get();
        $screenshots = collect($catalogGame->screenshots ?? [])
            ->map(fn (string $url, int $index): array => [
                'url' => $url,
                'caption' => 'Скриншот '.($index + 1)." из игры {$catalogGame->title}",
                'user' => null,
            ])
            ->concat($userScreenshots->map(fn (GameScreenshot $screenshot): array => [
                'url' => $screenshot->url,
                'caption' => "Скриншот {$screenshot->game->gameList->user->login} из игры {$catalogGame->title}",
                'user' => $screenshot->game->gameList->user,
            ]))
            ->values();

        return view('games.show', [
            'catalogGame' => $catalogGame,
            'coverUrl' => $coverGame?->cover_url ?? $catalogGame->cover_url,
            'statusCounts' => $statusCounts,
            'totalAdditions' => $statusCounts->sum(),
            'ratingAverage' => $catalogGame->reviews()->whereNotNull('rating')->avg('rating'),
            'ratingCount' => $catalogGame->reviews()->whereNotNull('rating')->count(),
            'reviews' => $reviews,
            'userReview' => $userReview,
            'userLists' => $userLists,
            'availableLists' => $userLists,
            'addedListsCount' => $addedListIds->count(),
            'screenshots' => $screenshots,
        ]);
    }
}
