<?php

namespace App\Services;

use App\Models\CatalogGame;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CatalogGameCache
{
    public function __construct(private readonly GameTitleNormalizer $normalizer) {}

    /** @return array<int, array<string, int|string|null>> */
    public function search(string $query, int $limit = 12): array
    {
        $normalized = $this->normalizer->normalize($query);

        return CatalogGame::query()
            ->where('normalized_title', 'like', '%'.$normalized.'%')
            ->orderByRaw(
                'CASE WHEN normalized_title = ? THEN 0 WHEN normalized_title LIKE ? THEN 1 ELSE 2 END',
                [$normalized, $normalized.'%'],
            )
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->map(fn (CatalogGame $game): array => [
                'id' => $game->hltb_id,
                'title' => $game->title,
                'cover_url' => $game->cover_url,
                'main_story_minutes' => $game->main_story_minutes,
                'main_extra_minutes' => null,
                'completionist_minutes' => $game->completionist_minutes,
            ])
            ->all();
    }

    /** @return LengthAwarePaginator<CatalogGame> */
    public function paginate(
        string $query = '',
        ?string $genreSlug = null,
        ?int $platformId = null,
        int $perPage = 20,
    ): LengthAwarePaginator {
        $normalized = $this->normalizer->normalize($query);
        $games = CatalogGame::query();

        if ($genreSlug !== null) {
            $games->whereJsonContains('genre_slugs', $genreSlug);
        }

        if ($platformId !== null) {
            $games->whereJsonContains('platform_ids', $platformId);
        }

        if ($normalized !== '') {
            $games
                ->where('normalized_title', 'like', '%'.$normalized.'%')
                ->orderByRaw(
                    'CASE WHEN normalized_title = ? THEN 0 WHEN normalized_title LIKE ? THEN 1 ELSE 2 END',
                    [$normalized, $normalized.'%'],
                )
                ->orderByDesc('rawg_added');
        } elseif ($genreSlug !== null || $platformId !== null) {
            $games->orderByDesc('rawg_added')->latest('updated_at');
        } else {
            $games->latest('updated_at');
        }

        return $games->paginate($perPage)->withQueryString();
    }

    /** @param array<int, array<string, int|string|null>> $games */
    public function store(array $games): void
    {
        $rows = collect($games)
            ->filter(fn (array $game): bool => (int) ($game['id'] ?? 0) > 0 && trim((string) ($game['title'] ?? '')) !== '')
            ->map(fn (array $game): array => [
                'hltb_id' => (int) $game['id'],
                'title' => (string) $game['title'],
                'normalized_title' => $this->normalizer->normalize((string) $game['title']),
                'cover_url' => $game['cover_url'] ?? null,
                'main_story_minutes' => $game['main_story_minutes'] ?? null,
                'completionist_minutes' => $game['completionist_minutes'] ?? null,
            ])
            ->values()
            ->all();

        if ($rows === []) {
            return;
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                $catalogGame = CatalogGame::query()->where('hltb_id', $row['hltb_id'])->first()
                    ?? CatalogGame::query()
                        ->whereNull('hltb_id')
                        ->where('normalized_title', $row['normalized_title'])
                        ->first()
                    ?? new CatalogGame;

                $catalogGame->fill($row)->save();
            }
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $games
     */
    public function storeRawg(array $games): int
    {
        $rows = collect($games)
            ->filter(fn (array $game): bool => (int) ($game['rawg_id'] ?? 0) > 0 && trim((string) ($game['title'] ?? '')) !== '')
            ->map(function (array $game): array {
                $title = trim((string) $game['title']);

                return [
                    'rawg_id' => (int) $game['rawg_id'],
                    'rawg_slug' => $game['rawg_slug'] ?? null,
                    'title' => $title,
                    'normalized_title' => $this->normalizer->normalize($title),
                    'cover_url' => $game['cover_url'] ?? null,
                    'screenshots' => $game['screenshots'] ?? [],
                    'genres' => $game['genres'] ?? [],
                    'genre_slugs' => $game['genre_slugs'] ?? [],
                    'platforms' => $game['platforms'] ?? [],
                    'platform_ids' => $game['platform_ids'] ?? [],
                    'age_rating' => $game['age_rating'] ?? null,
                    'rawg_added' => $game['rawg_added'] ?? null,
                ];
            })
            ->values()
            ->all();

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $row) {
                $catalogGame = CatalogGame::query()->where('rawg_id', $row['rawg_id'])->first()
                    ?? CatalogGame::query()
                        ->whereNull('rawg_id')
                        ->where('normalized_title', $row['normalized_title'])
                        ->first()
                    ?? new CatalogGame;

                $attributes = array_filter($row, fn (mixed $value): bool => $value !== null);
                if ($catalogGame->rawg_synced_at !== null && ! empty($catalogGame->screenshots)) {
                    unset($attributes['screenshots']);
                }

                $catalogGame->fill($attributes)->save();
            }
        });

        return count($rows);
    }
}
