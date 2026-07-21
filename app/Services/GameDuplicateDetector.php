<?php

namespace App\Services;

use App\Models\Game;

class GameDuplicateDetector
{
    public function find(int $userId, string $title, ?int $catalogGameId): ?Game
    {
        return Game::query()
            ->whereHas('gameList', fn ($query) => $query->where('user_id', $userId))
            ->where(function ($query) use ($title, $catalogGameId): void {
                $query->where('title', trim($title));

                if ($catalogGameId !== null) {
                    $query->orWhere('catalog_game_id', $catalogGameId);
                }
            })
            ->with('gameList')
            ->oldest('id')
            ->first();
    }

    /** @return array{id: int, title: string, list: string, edit_url: string} */
    public function details(Game $game): array
    {
        $game->loadMissing('gameList');

        return [
            'id' => $game->id,
            'title' => $game->title,
            'list' => $game->gameList->name,
            'edit_url' => route('games.edit', $game),
        ];
    }
}
