<?php

namespace App\Observers;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Services\AchievementService;
use App\Services\CatalogGameResolver;
use App\Services\SocialNotificationService;

class GameObserver
{
    public function __construct(
        private readonly SocialNotificationService $notifications,
        private readonly CatalogGameResolver $catalogGames,
        private readonly AchievementService $achievements,
    ) {}

    public function saving(Game $game): void
    {
        $game->catalog_game_id = $this->catalogGames->resolve($game)?->id;
    }

    public function created(Game $game): void
    {
        $game->loadMissing('gameList.user');
        $owner = $game->gameList->user;

        if ($game->gameList->is_public) {
            $this->notifications->notifyFollowers(
                $owner,
                'public_game_added',
                "@{$owner->login} добавил игру «{$game->title}» в публичный список «{$game->gameList->name}».",
                $this->publicListUrl($game),
                'sports_esports',
                ['game_id' => $game->id],
            );
        }

        $this->achievements->evaluate($owner);
    }

    public function updated(Game $game): void
    {
        if (! $game->wasChanged('status')) {
            return;
        }

        $game->loadMissing('gameList.user');
        $owner = $game->gameList->user;

        if ($game->status === GameStatus::Playing) {
            $this->notifications->notifyUser(
                $owner,
                'good_luck',
                "Удачи в прохождении «{$game->title}»!",
                route('games.view', $game, false),
                'play_circle',
                ['game_id' => $game->id],
            );

            if ($game->gameList->is_public) {
                $this->notifications->notifyFollowers(
                    $owner,
                    'friend_started_game',
                    "@{$owner->login} начал играть в «{$game->title}».",
                    $this->publicListUrl($game),
                    'play_circle',
                    ['game_id' => $game->id],
                );
            }
        }

        if ($game->status === GameStatus::Completed) {
            $this->notifications->notifyUser(
                $owner,
                'congratulations',
                "Поздравляем с прохождением «{$game->title}»!",
                route('games.view', $game, false),
                'trophy',
                ['game_id' => $game->id],
            );

            if ($game->gameList->is_public) {
                $this->notifications->notifyFollowers(
                    $owner,
                    'friend_completed_game',
                    "@{$owner->login} прошёл игру «{$game->title}».",
                    $this->publicListUrl($game),
                    'trophy',
                    ['game_id' => $game->id],
                );
            }
        }

        $this->achievements->evaluate($owner);
    }

    private function publicListUrl(Game $game): string
    {
        return route('public.lists.show', [$game->gameList->user->login, $game->gameList->slug], false);
    }
}
