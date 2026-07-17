<?php

namespace App\Services;

use App\Models\CatalogGame;

class CatalogFilterOptions
{
    private const DEFAULT_GENRES = [
        'action' => 'Action',
        'adventure' => 'Adventure',
        'arcade' => 'Arcade',
        'board-games' => 'Board Games',
        'card' => 'Card',
        'casual' => 'Casual',
        'educational' => 'Educational',
        'family' => 'Family',
        'fighting' => 'Fighting',
        'indie' => 'Indie',
        'massively-multiplayer' => 'Massively Multiplayer',
        'platformer' => 'Platformer',
        'puzzle' => 'Puzzle',
        'racing' => 'Racing',
        'role-playing-games-rpg' => 'RPG',
        'shooter' => 'Shooter',
        'simulation' => 'Simulation',
        'sports' => 'Sports',
        'strategy' => 'Strategy',
    ];

    private const DEFAULT_PLATFORMS = [
        21 => 'Android',
        3 => 'iOS',
        6 => 'Linux',
        5 => 'macOS',
        7 => 'Nintendo Switch',
        4 => 'PC',
        16 => 'PlayStation 3',
        18 => 'PlayStation 4',
        187 => 'PlayStation 5',
        14 => 'Xbox 360',
        1 => 'Xbox One',
        186 => 'Xbox Series S/X',
    ];

    /**
     * @return array{
     *     genres: array<int, array{value: string, label: string}>,
     *     platforms: array<int, array{value: int, label: string}>
     * }
     */
    public function all(): array
    {
        $genres = collect(self::DEFAULT_GENRES)
            ->mapWithKeys(fn (string $label, string $slug): array => [
                $slug => ['value' => $slug, 'label' => $label],
            ])
            ->all();
        $platforms = collect(self::DEFAULT_PLATFORMS)
            ->mapWithKeys(fn (string $label, int $id): array => [
                $id => ['value' => $id, 'label' => $label],
            ])
            ->all();

        CatalogGame::query()
            ->select(['id', 'genres', 'genre_slugs', 'platforms', 'platform_ids'])
            ->where(function ($query): void {
                $query->whereNotNull('genre_slugs')->orWhereNotNull('platform_ids');
            })
            ->lazyById(200)
            ->each(function (CatalogGame $game) use (&$genres, &$platforms): void {
                foreach ($game->genre_slugs ?? [] as $index => $slug) {
                    $label = trim((string) ($game->genres[$index] ?? ''));
                    if (is_string($slug) && $slug !== '' && $label !== '') {
                        $genres[$slug] ??= ['value' => $slug, 'label' => $label];
                    }
                }

                foreach ($game->platform_ids ?? [] as $index => $id) {
                    $id = (int) $id;
                    $label = trim((string) ($game->platforms[$index] ?? ''));
                    if ($id > 0 && $label !== '') {
                        $platforms[$id] ??= ['value' => $id, 'label' => $label];
                    }
                }
            });

        uasort($genres, fn (array $left, array $right): int => strnatcasecmp($left['label'], $right['label']));
        uasort($platforms, fn (array $left, array $right): int => strnatcasecmp($left['label'], $right['label']));

        return [
            'genres' => array_values($genres),
            'platforms' => array_values($platforms),
        ];
    }
}
