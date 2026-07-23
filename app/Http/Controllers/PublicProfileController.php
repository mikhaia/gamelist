<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use App\Services\UserProfileStats;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    public function __construct(private readonly UserProfileStats $profileStats) {}

    public function __invoke(Request $request, string $login): View
    {
        $profile = User::query()
            ->where('login', strtolower($login))
            ->with('achievements')
            ->firstOrFail();
        $isOwner = $request->user()?->is($profile) ?? false;
        $publicLists = $profile->gameLists()
            ->where('is_public', true)
            ->withCount('games')
            ->orderByLatestGameUpdate()
            ->get();
        $favoriteGames = $profile->favoriteGames()
            ->with('gameList')
            ->when(! $isOwner, fn ($query) => $query->whereHas('gameList', fn ($gameLists) => $gameLists->where('is_public', true)))
            ->get();
        $publicGames = Game::query()
            ->whereHas('gameList', fn ($query) => $query
                ->where('user_id', $profile->getKey())
                ->where('is_public', true));
        $recentGamesByStatus = [
            GameStatus::WantToPlay->value => (clone $publicGames)
                ->whereIn('status', [GameStatus::WantToPlay->value, GameStatus::WantToReplay->value])
                ->latest('created_at')
                ->latest('id')
                ->limit(3)
                ->get(),
            GameStatus::Playing->value => (clone $publicGames)
                ->whereIn('status', [GameStatus::Playing->value, GameStatus::Replaying->value])
                ->whereNotNull('started_at')
                ->orderByDesc('started_at')
                ->latest('id')
                ->limit(3)
                ->get(),
            GameStatus::Completed->value => (clone $publicGames)
                ->whereIn('status', [GameStatus::Completed->value, GameStatus::Completed100->value])
                ->whereNotNull('completed_at')
                ->orderByDesc('completed_at')
                ->latest('id')
                ->limit(3)
                ->get(),
        ];
        $availableGames = $isOwner
            ? Game::query()
                ->whereHas('gameList', fn ($query) => $query->where('user_id', $profile->getKey()))
                ->with('gameList')
                ->orderBy('title')
                ->get()
            : collect();

        $stats = $this->profileStats->forUser($profile);
        $profileStatusCounts = [
            GameStatus::WantToPlay->value => $stats['statuses'][GameStatus::WantToPlay->value] + $stats['statuses'][GameStatus::WantToReplay->value],
            GameStatus::Playing->value => $stats['statuses'][GameStatus::Playing->value] + $stats['statuses'][GameStatus::Replaying->value],
            GameStatus::Completed->value => $stats['statuses'][GameStatus::Completed->value] + $stats['statuses'][GameStatus::Completed100->value],
        ];

        return view('profiles.show', [
            'profile' => $profile,
            'publicLists' => $publicLists,
            'favoriteGames' => $favoriteGames,
            'recentGamesByStatus' => $recentGamesByStatus,
            'availableGames' => $availableGames,
            'stats' => $stats,
            'profileStatusCounts' => $profileStatusCounts,
            'isOwner' => $isOwner,
            'isFriend' => $request->user()?->isFriendsWith($profile) ?? false,
        ]);
    }
}
