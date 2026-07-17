<?php

namespace App\Services;

use App\Models\CatalogGame;
use App\Models\Game;

class CatalogGameResolver
{
    public function resolve(Game $game): ?CatalogGame
    {
        if ($game->catalog_game_id) {
            return CatalogGame::query()->find($game->catalog_game_id);
        }

        if ($game->hltb_id) {
            return CatalogGame::query()->where('hltb_id', $game->hltb_id)->first();
        }

        return $game->normalized_title
            ? CatalogGame::query()->where('normalized_title', $game->normalized_title)->first()
            : null;
    }
}
