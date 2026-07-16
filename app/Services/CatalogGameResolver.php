<?php

namespace App\Services;

use App\Models\CatalogGame;
use App\Models\Game;

class CatalogGameResolver
{
    public function resolve(Game $game): ?CatalogGame
    {
        return $game->hltb_id
            ? CatalogGame::query()->where('hltb_id', $game->hltb_id)->first()
            : null;
    }
}
