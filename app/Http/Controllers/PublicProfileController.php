<?php

namespace App\Http\Controllers;

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
            ->firstOrFail();
        $publicLists = $profile->gameLists()
            ->where('is_public', true)
            ->withCount('games')
            ->latest()
            ->get();
        $favoriteGames = $profile->favoriteGames()
            ->with('gameList')
            ->get();
        $isOwner = $request->user()?->is($profile) ?? false;
        $availableGames = $isOwner
            ? Game::query()
                ->whereHas('gameList', fn ($query) => $query->where('user_id', $profile->getKey()))
                ->with('gameList')
                ->orderBy('title')
                ->get()
            : collect();

        return view('profiles.show', [
            'profile' => $profile,
            'publicLists' => $publicLists,
            'favoriteGames' => $favoriteGames,
            'availableGames' => $availableGames,
            'stats' => $this->profileStats->forUser($profile),
            'isOwner' => $isOwner,
            'isFriend' => $request->user()?->isFriendsWith($profile) ?? false,
        ]);
    }
}
