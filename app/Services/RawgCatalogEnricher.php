<?php

namespace App\Services;

use App\Models\CatalogGame;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class RawgCatalogEnricher
{
    public function __construct(private readonly GameTitleNormalizer $titles) {}

    public function enrich(CatalogGame $catalogGame): CatalogGame
    {
        $key = trim((string) config('services.rawg.key'));

        if ($key === '' || ! $this->needsSync($catalogGame)) {
            return $catalogGame;
        }

        $search = $this->get('games', [
            'key' => $key,
            'search' => $catalogGame->title,
            'search_precise' => true,
            'page_size' => 10,
        ], $catalogGame);

        if ($search === null) {
            return $catalogGame;
        }

        $results = $search->json('results');
        if (! is_array($results)) {
            Log::warning('RAWG returned an invalid games search payload.', [
                'catalog_game_id' => $catalogGame->id,
            ]);

            return $catalogGame;
        }

        $title = $this->normalizeTitle($catalogGame->title);
        $match = collect($results)->first(
            fn (mixed $result): bool => is_array($result)
                && $this->normalizeTitle((string) ($result['name'] ?? '')) === $title,
        );

        if (! is_array($match) || (int) ($match['id'] ?? 0) < 1) {
            Log::info('RAWG has no exact title match for a catalog game.', [
                'catalog_game_id' => $catalogGame->id,
                'title' => $catalogGame->title,
            ]);

            return $catalogGame;
        }

        $rawgId = (int) $match['id'];
        $screenshotsResponse = $this->get("games/{$rawgId}/screenshots", [
            'key' => $key,
            'page_size' => 20,
        ], $catalogGame);
        $storesResponse = $this->get("games/{$rawgId}/stores", [
            'key' => $key,
        ], $catalogGame);

        $backgroundImage = $this->httpsUrl($match['background_image'] ?? null);
        $screenshotItems = $screenshotsResponse?->json('results');
        if (! is_array($screenshotItems)) {
            $screenshotItems = is_array($match['short_screenshots'] ?? null)
                ? $match['short_screenshots']
                : [];
        }

        $screenshots = array_values(array_filter(
            $this->imageUrls($screenshotItems),
            fn (string $url): bool => $url !== $backgroundImage,
        ));

        $attributes = [
            'rawg_id' => $rawgId,
            'rawg_slug' => $this->slug($match['slug'] ?? null),
            'screenshots' => $screenshots,
            'genres' => $this->names($match['genres'] ?? null, 'name'),
            'genre_slugs' => $this->slugs($match['genres'] ?? null, 'slug'),
            'age_rating' => $this->label(data_get($match, 'esrb_rating.name')),
            'platforms' => $this->names($match['platforms'] ?? null, 'platform.name'),
            'platform_ids' => $this->integers($match['platforms'] ?? null, 'platform.id'),
            'rawg_added' => max(0, (int) ($match['added'] ?? 0)),
            'rawg_synced_at' => now(),
        ];

        $stores = $storesResponse?->json('results');
        if (is_array($stores)) {
            $attributes['steam_id'] = $this->steamId($stores);
        }

        $catalogGame->fill($attributes)->save();

        return $catalogGame->refresh();
    }

    private function needsSync(CatalogGame $catalogGame): bool
    {
        if ((! empty($catalogGame->genres) && count($catalogGame->genres) !== count($catalogGame->genre_slugs ?? []))
            || (! empty($catalogGame->platforms) && count($catalogGame->platforms) !== count($catalogGame->platform_ids ?? []))) {
            return true;
        }

        if ($catalogGame->rawg_synced_at === null) {
            return true;
        }

        $ttlDays = max(1, (int) config('services.rawg.sync_ttl_days', 30));

        return $catalogGame->rawg_synced_at->lte(now()->subDays($ttlDays));
    }

    /** @param array<string, mixed> $query */
    private function get(string $endpoint, array $query, CatalogGame $catalogGame): ?Response
    {
        try {
            $response = $this->client()->get($endpoint, $query);
        } catch (Throwable $exception) {
            Log::warning('RAWG request failed.', [
                'catalog_game_id' => $catalogGame->id,
                'endpoint' => $endpoint,
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            Log::warning('RAWG returned an unsuccessful response.', [
                'catalog_game_id' => $catalogGame->id,
                'endpoint' => $endpoint,
                'status' => $response->status(),
            ]);

            return null;
        }

        return $response;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.rawg.base_url'), '/'))
            ->acceptJson()
            ->connectTimeout(3)
            ->timeout(8)
            ->retry(2, 250, throw: false);
    }

    private function normalizeTitle(string $title): string
    {
        if (class_exists(\Normalizer::class)) {
            $title = \Normalizer::normalize($title, \Normalizer::FORM_KC) ?: $title;
        }

        return $this->titles->normalize($title);
    }

    private function slug(mixed $slug): ?string
    {
        if (! is_string($slug) || preg_match('/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', $slug) !== 1) {
            return null;
        }

        return $slug;
    }

    private function label(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }

    /**
     * @return array<int, string>
     */
    private function names(mixed $items, string $path): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(fn (mixed $item): mixed => is_array($item) ? data_get($item, $path) : null)
            ->filter(fn (mixed $name): bool => is_string($name) && trim($name) !== '')
            ->map(fn (string $name): string => trim($name))
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    private function slugs(mixed $items, string $path): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(fn (mixed $item): mixed => is_array($item) ? data_get($item, $path) : null)
            ->filter(fn (mixed $slug): bool => $this->slug($slug) !== null)
            ->map(fn (string $slug): string => $slug)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, int> */
    private function integers(mixed $items, string $path): array
    {
        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(fn (mixed $item): mixed => is_array($item) ? data_get($item, $path) : null)
            ->filter(fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false && (int) $id > 0)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param  array<int, mixed>  $items
     * @return array<int, string>
     */
    private function imageUrls(array $items): array
    {
        return collect($items)
            ->filter(fn (mixed $item): bool => is_array($item) && data_get($item, 'is_deleted') !== true)
            ->map(fn (array $item): ?string => $this->httpsUrl(data_get($item, 'image')))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function httpsUrl(mixed $url): ?string
    {
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        return strtolower((string) parse_url($url, PHP_URL_SCHEME)) === 'https' ? $url : null;
    }

    /** @param array<int, mixed> $stores */
    private function steamId(array $stores): ?string
    {
        foreach ($stores as $store) {
            if (! is_array($store) || ! is_string($store['url'] ?? null)) {
                continue;
            }

            $parts = parse_url($store['url']);
            if (! is_array($parts)) {
                continue;
            }

            $host = strtolower((string) ($parts['host'] ?? ''));
            $scheme = strtolower((string) ($parts['scheme'] ?? ''));
            $path = (string) ($parts['path'] ?? '');
            $isSteamHost = in_array($host, ['store.steampowered.com', 'www.store.steampowered.com'], true);

            if (! $isSteamHost || ! in_array($scheme, ['http', 'https'], true)) {
                continue;
            }

            if (preg_match('#(?:^|/)app/(\d+)(?:/|$)#', $path, $matches) === 1) {
                return $matches[1];
            }
        }

        return null;
    }
}
