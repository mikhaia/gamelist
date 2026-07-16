<?php

namespace App\Observers;

use App\Models\GameList;
use App\Services\SocialNotificationService;

class GameListObserver
{
    public function __construct(private readonly SocialNotificationService $notifications) {}

    public function created(GameList $gameList): void
    {
        if ($gameList->is_public) {
            $this->notifyPublished($gameList, 'создал новый публичный список');
        }
    }

    public function updated(GameList $gameList): void
    {
        if ($gameList->wasChanged('is_public') && $gameList->is_public) {
            $this->notifyPublished($gameList, 'открыл публичный список');
        }
    }

    private function notifyPublished(GameList $gameList, string $action): void
    {
        $gameList->loadMissing('user');
        $url = route('public.lists.show', [$gameList->user->login, $gameList->slug], false);

        $this->notifications->notifyFollowers(
            $gameList->user,
            'public_list_created',
            "@{$gameList->user->login} {$action} «{$gameList->name}».",
            $url,
            'playlist_add',
        );
    }
}
