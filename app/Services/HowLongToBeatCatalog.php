<?php

namespace App\Services;

use AneesKhan47\HowLongToBeat\HowLongToBeat;
use App\Contracts\GameCatalog;

class HowLongToBeatCatalog implements GameCatalog
{
    public function search(string $query, int $limit = 8): array
    {
        $result = (new HowLongToBeat)->searchByTitle($query, 1, min($limit, 25));

        return array_map(static fn ($game): array => [
            'id' => (int) $game->id,
            'title' => (string) $game->name,
            'cover_url' => $game->image_url,
            'main_story_minutes' => $game->main_story_time ? (int) round($game->main_story_time / 60) : null,
            'main_extra_minutes' => $game->main_extra_time ? (int) round($game->main_extra_time / 60) : null,
            'completionist_minutes' => $game->completionist_time ? (int) round($game->completionist_time / 60) : null,
        ], $result->items());
    }
}
