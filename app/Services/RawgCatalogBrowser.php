<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RawgCatalogBrowser
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function search(string $query = '', ?string $genreSlug = null, ?int $platformId = null): array
    {
        $key = trim((string) config('services.rawg.key'));
        if ($key === '') {
            throw new RuntimeException('RAWG API key is not configured.');
        }

        $parameters = array_filter([
            'search' => trim($query) !== '' ? trim($query) : null,
            'genres' => $genreSlug,
            'platforms' => $platformId,
            'ordering' => '-added',
            'page' => 1,
            'page_size' => 40,
        ], fn (mixed $value): bool => $value !== null && $value !== '');

        $cacheKey = 'rawg:catalog-search:'.hash('sha256', json_encode($parameters, JSON_THROW_ON_ERROR));
        $ttlHours = max(1, (int) config('services.rawg.search_ttl_hours', 6));

        return Cache::remember($cacheKey, now()->addHours($ttlHours), function () use ($key, $parameters): array {
            $response = $this->client()->get('games', ['key' => $key, ...$parameters]);
            if (! $response->successful()) {
                throw new RuntimeException("RAWG search failed with HTTP {$response->status()}.");
            }

            $results = $response->json('results');
            if (! is_array($results)) {
                throw new RuntimeException('RAWG returned an invalid games search payload.');
            }

            return collect($results)
                ->map(fn (mixed $game): ?array => $this->normalizeGame($game))
                ->filter()
                ->values()
                ->all();
        });
    }

    /** @return array<string, mixed>|null */
    private function normalizeGame(mixed $game): ?array
    {
        if (! is_array($game) || (int) ($game['id'] ?? 0) < 1 || trim((string) ($game['name'] ?? '')) === '') {
            return null;
        }

        $genres = collect(is_array($game['genres'] ?? null) ? $game['genres'] : [])
            ->filter(fn (mixed $genre): bool => is_array($genre)
                && trim((string) ($genre['name'] ?? '')) !== ''
                && $this->validSlug($genre['slug'] ?? null))
            ->map(fn (array $genre): array => [
                'name' => trim((string) $genre['name']),
                'slug' => (string) $genre['slug'],
            ])
            ->unique('slug')
            ->values();
        $platforms = collect(is_array($game['platforms'] ?? null) ? $game['platforms'] : [])
            ->map(fn (mixed $item): mixed => is_array($item) ? ($item['platform'] ?? null) : null)
            ->filter(fn (mixed $platform): bool => is_array($platform)
                && (int) ($platform['id'] ?? 0) > 0
                && trim((string) ($platform['name'] ?? '')) !== '')
            ->map(fn (array $platform): array => [
                'id' => (int) $platform['id'],
                'name' => trim((string) $platform['name']),
            ])
            ->unique('id')
            ->values();
        $background = $this->httpsUrl($game['background_image'] ?? null);
        $screenshots = collect(is_array($game['short_screenshots'] ?? null) ? $game['short_screenshots'] : [])
            ->map(fn (mixed $item): ?string => is_array($item) ? $this->httpsUrl($item['image'] ?? null) : null)
            ->filter(fn (?string $url): bool => $url !== null && $url !== $background)
            ->unique()
            ->values()
            ->all();

        return [
            'rawg_id' => (int) $game['id'],
            'rawg_slug' => $this->validSlug($game['slug'] ?? null) ? (string) $game['slug'] : null,
            'title' => trim((string) $game['name']),
            'cover_url' => $background,
            'screenshots' => $screenshots,
            'genres' => $genres->pluck('name')->all(),
            'genre_slugs' => $genres->pluck('slug')->all(),
            'platforms' => $platforms->pluck('name')->all(),
            'platform_ids' => $platforms->pluck('id')->all(),
            'age_rating' => $this->label(data_get($game, 'esrb_rating.name')),
            'rawg_added' => max(0, (int) ($game['added'] ?? 0)),
        ];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.rawg.base_url'), '/'))
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(8)
            ->retry(2, 250, throw: false);
    }

    private function validSlug(mixed $slug): bool
    {
        return is_string($slug) && preg_match('/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', $slug) === 1;
    }

    private function httpsUrl(mixed $url): ?string
    {
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : null;
    }

    private function label(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
