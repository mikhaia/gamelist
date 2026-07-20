<?php

namespace App\Services;

use App\Models\Game;
use App\Models\User;

class GameAccess
{
    public function canView(?User $user, Game $game): bool
    {
        $game->loadMissing('gameList');

        return $game->gameList->is_public || $user?->id === $game->gameList->user_id;
    }

    public function authorizeView(?User $user, Game $game): void
    {
        abort_unless($this->canView($user, $game), 403);
    }

    public function isOwner(?User $user, Game $game): bool
    {
        $game->loadMissing('gameList');

        return $user?->id === $game->gameList->user_id;
    }

    public function authorizeOwner(?User $user, Game $game): void
    {
        abort_unless($this->isOwner($user, $game), 403);
    }
}
