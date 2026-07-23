<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogGame;
use App\Models\GameComment;
use App\Models\GameReview;
use App\Models\GameScreenshot;
use App\Models\User;
use App\Services\AdminFileService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AdminFileService $files) {}

    public function __invoke(): View
    {
        $sevenDaysAgo = now()->subDays(7);
        $thirtyDaysAgo = now()->subDays(30);
        $chartStart = today()->subDays(6);
        $catalogGamesByDay = CatalogGame::query()
            ->where('created_at', '>=', $chartStart)
            ->get(['created_at'])
            ->countBy(fn (CatalogGame $game): string => $game->created_at->toDateString());
        $usersByLastSeenDay = User::query()
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $chartStart)
            ->get(['last_seen_at'])
            ->countBy(fn (User $user): string => $user->last_seen_at->toDateString());

        return view('admin.dashboard', [
            'stats' => [
                'users_total' => User::query()->count(),
                'users_7_days' => User::query()->where('created_at', '>=', $sevenDaysAgo)->count(),
                'users_30_days' => User::query()->where('created_at', '>=', $thirtyDaysAgo)->count(),
                'games_total' => CatalogGame::query()->count(),
                'games_7_days' => CatalogGame::query()->where('created_at', '>=', $sevenDaysAgo)->count(),
                'games_30_days' => CatalogGame::query()->where('created_at', '>=', $thirtyDaysAgo)->count(),
            ],
            'fileStats' => $this->files->totals(),
            'catalogGamesChart' => $this->dailySeries($chartStart, $catalogGamesByDay),
            'userActivityChart' => $this->dailySeries($chartStart, $usersByLastSeenDay),
            'latestUsers' => User::query()->latest()->limit(5)->get(),
            'latestGames' => CatalogGame::query()->latest()->limit(5)->get(),
            'latestScreenshots' => GameScreenshot::query()->with('game.gameList.user')->latest()->limit(5)->get(),
            'latestComments' => GameComment::query()->with(['user', 'game'])->latest()->limit(5)->get(),
            'latestReviews' => GameReview::query()->with(['user', 'catalogGame'])->latest()->limit(5)->get(),
        ]);
    }

    /**
     * @param  Collection<string, int>  $counts
     * @return array<int, array{date: string, label: string, weekday: string, count: int}>
     */
    private function dailySeries(Carbon $start, Collection $counts): array
    {
        return collect(range(0, 6))
            ->map(function (int $offset) use ($start, $counts): array {
                $date = $start->copy()->addDays($offset);

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('d.m'),
                    'weekday' => $date->locale(app()->getLocale())->isoFormat('dd'),
                    'count' => (int) $counts->get($date->toDateString(), 0),
                ];
            })
            ->all();
    }
}
