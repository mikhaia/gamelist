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
        $this->recordStatus($game);
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

        $this->recordStatus($game);
        $game->loadMissing('gameList.user');
        $owner = $game->gameList->user;

        if ($game->status->isInProgress()) {
            $replaying = $game->status === GameStatus::Replaying;
            $this->notifications->notifyUser(
                $owner,
                'good_luck',
                $replaying ? "Удачи в перепрохождении «{$game->title}»!" : "Удачи в прохождении «{$game->title}»!",
                route('games.view', $game, false),
                $replaying ? 'restart_alt' : 'play_circle',
                ['game_id' => $game->id],
            );

            if ($game->gameList->is_public) {
                $this->notifications->notifyFollowers(
                    $owner,
                    'friend_started_game',
                    $replaying
                        ? "@{$owner->login} перепроходит «{$game->title}»."
                        : "@{$owner->login} начал играть в «{$game->title}».",
                    $this->publicListUrl($game),
                    $replaying ? 'restart_alt' : 'play_circle',
                    ['game_id' => $game->id],
                );
            }
        }

        if ($game->status->isCompleted()) {
            $completed100 = $game->status === GameStatus::Completed100;
            $this->notifications->notifyUser(
                $owner,
                'congratulations',
                $completed100
                    ? "Поздравляем со 100% прохождением «{$game->title}»!"
                    : "Поздравляем с прохождением «{$game->title}»!",
                route('games.view', $game, false),
                $completed100 ? 'workspace_premium' : 'trophy',
                ['game_id' => $game->id],
            );

            if ($game->gameList->is_public) {
                $this->notifications->notifyFollowers(
                    $owner,
                    'friend_completed_game',
                    $completed100
                        ? "@{$owner->login} прошёл «{$game->title}» на 100%."
                        : "@{$owner->login} прошёл игру «{$game->title}».",
                    $this->publicListUrl($game),
                    $completed100 ? 'workspace_premium' : 'trophy',
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

    private function recordStatus(Game $game): void
    {
        $game->statusEvents()->create([
            'status' => $game->status,
            'changed_at' => $game->updated_at ?? now(),
        ]);
    }
}
