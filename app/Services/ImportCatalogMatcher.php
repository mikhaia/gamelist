<?php

namespace App\Services;

use App\Contracts\GameCatalog;
use App\Models\CatalogGame;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportCatalogMatcher
{
    /** @var array<string, array<int, array{id: int, title: string, normalized_title: string, cover_url: ?string}>> */
    private array $matches = [];

    public function __construct(
        private readonly GameCatalog $externalCatalog,
        private readonly CatalogGameCache $cache,
        private readonly GameTitleNormalizer $normalizer,
    ) {}

    /** @return array<int, array{id: int, title: string, normalized_title: string, cover_url: ?string}> */
    public function forTitle(string $title, int $limit = 6): array
    {
        $normalized = $this->normalizer->normalize($title);
        if ($normalized === '') {
            return [];
        }

        if (array_key_exists($normalized, $this->matches)) {
            return $this->matches[$normalized];
        }

        $matches = $this->localMatches($normalized, $limit);
        if ($matches !== []) {
            return $this->matches[$normalized] = $matches;
        }

        try {
            $this->cache->store($this->externalCatalog->search($title, $limit));
        } catch (Throwable $exception) {
            Log::warning('External game catalog lookup failed during import preview.', [
                'title' => $title,
                'exception' => $exception,
            ]);
        }

        return $this->matches[$normalized] = $this->localMatches($normalized, $limit);
    }

    /** @return array<int, array{id: int, title: string, normalized_title: string, cover_url: ?string}> */
    private function localMatches(string $normalized, int $limit): array
    {
        $tokens = $this->tokens($normalized)->sortByDesc(fn (string $token): int => mb_strlen($token))->take(5);
        $games = CatalogGame::query()
            ->where(function ($query) use ($normalized, $tokens): void {
                $query->where('normalized_title', 'like', '%'.$normalized.'%');
                $tokens->each(fn (string $token) => $query->orWhere('normalized_title', 'like', '%'.$token.'%'));
            })
            ->limit(40)
            ->get()
            ->sortByDesc(fn (CatalogGame $game): float => $this->score($normalized, $game->normalized_title))
            ->take($limit)
            ->values();

        return $games->map(fn (CatalogGame $game): array => [
            'id' => $game->id,
            'title' => $game->title,
            'normalized_title' => $game->normalized_title,
            'cover_url' => $game->cover_url,
        ])->all();
    }

    /** @return Collection<int, string> */
    private function tokens(string $title): Collection
    {
        $ignored = ['the', 'and', 'for', 'with', 'edition', 'game', 'игра', 'для'];

        return collect(preg_split('/[^\pL\pN]+/u', $title) ?: [])
            ->filter(fn (string $token): bool => mb_strlen($token) >= 3 && ! in_array($token, $ignored, true))
            ->unique()
            ->values();
    }

    private function score(string $expected, string $candidate): float
    {
        if ($candidate === $expected) {
            return 10000;
        }

        $expectedTokens = $this->tokens($expected);
        $candidateTokens = $this->tokens($candidate);
        $overlap = $expectedTokens->intersect($candidateTokens)->count();
        $coverage = $overlap / max(1, $expectedTokens->count());
        similar_text($expected, $candidate, $similarity);

        return ($coverage * 1000)
            + ((str_contains($candidate, $expected) || str_contains($expected, $candidate)) ? 500 : 0)
            + $similarity;
    }
}
