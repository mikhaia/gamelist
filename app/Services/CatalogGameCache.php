<?php

namespace App\Services;

use App\Models\CatalogGame;
use Illuminate\Pagination\LengthAwarePaginator;

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
    public function paginate(string $query = '', int $perPage = 20): LengthAwarePaginator
    {
        $normalized = $this->normalizer->normalize($query);
        $games = CatalogGame::query();

        if ($normalized !== '') {
            $games
                ->where('normalized_title', 'like', '%'.$normalized.'%')
                ->orderByRaw(
                    'CASE WHEN normalized_title = ? THEN 0 WHEN normalized_title LIKE ? THEN 1 ELSE 2 END',
                    [$normalized, $normalized.'%'],
                );
        } else {
            $games->latest('updated_at');
        }

        return $games->paginate($perPage)->withQueryString();
    }

    /** @param array<int, array<string, int|string|null>> $games */
    public function store(array $games): void
    {
        $now = now();
        $rows = collect($games)
            ->filter(fn (array $game): bool => (int) ($game['id'] ?? 0) > 0 && trim((string) ($game['title'] ?? '')) !== '')
            ->map(fn (array $game): array => [
                'hltb_id' => (int) $game['id'],
                'title' => (string) $game['title'],
                'normalized_title' => $this->normalizer->normalize((string) $game['title']),
                'cover_url' => $game['cover_url'] ?? null,
                'main_story_minutes' => $game['main_story_minutes'] ?? null,
                'completionist_minutes' => $game['completionist_minutes'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        if ($rows === []) {
            return;
        }

        CatalogGame::upsert(
            $rows,
            ['hltb_id'],
            ['title', 'normalized_title', 'cover_url', 'main_story_minutes', 'completionist_minutes', 'updated_at'],
        );
    }
}
