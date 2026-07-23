<?php

namespace App\Services;

use App\Exceptions\SteamLibraryException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class SteamWebApi
{
    /**
     * @return array<int, array{appid: int, name: string, playtime_minutes: int}>
     *
     * @throws SteamLibraryException
     */
    public function ownedGames(string $steamId): array
    {
        $key = trim((string) config('services.steam.key'));
        if ($key === '') {
            throw new SteamLibraryException(__('app.errors.steam_api_key_missing'));
        }

        try {
            $response = $this->client()->get('IPlayerService/GetOwnedGames/v0001/', [
                'key' => $key,
                'steamid' => $steamId,
                'format' => 'json',
                'include_appinfo' => true,
                'include_played_free_games' => true,
            ]);
        } catch (Throwable) {
            throw new SteamLibraryException(__('app.errors.steam_library_unavailable'));
        }

        $this->ensureSuccessful($response);
        $payload = $response->json('response');

        if (! is_array($payload)) {
            throw new SteamLibraryException(__('app.errors.steam_library_invalid'));
        }

        if (! array_key_exists('game_count', $payload) && ! array_key_exists('games', $payload)) {
            throw new SteamLibraryException(__('app.errors.steam_library_private'));
        }

        $games = $payload['games'] ?? [];
        if (! is_array($games)) {
            throw new SteamLibraryException(__('app.errors.steam_library_invalid'));
        }

        return collect($games)
            ->filter(fn (mixed $game): bool => is_array($game)
                && (int) ($game['appid'] ?? 0) > 0
                && trim((string) ($game['name'] ?? '')) !== '')
            ->map(fn (array $game): array => [
                'appid' => (int) $game['appid'],
                'name' => trim((string) $game['name']),
                'playtime_minutes' => max(0, (int) ($game['playtime_forever'] ?? 0)),
            ])
            ->unique('appid')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $appIds
     * @return array<int, int>
     */
    public function completedAchievementAppIds(string $steamId, array $appIds): array
    {
        $key = trim((string) config('services.steam.key'));
        $appIds = collect($appIds)
            ->map(fn (mixed $appId): int => (int) $appId)
            ->filter(fn (int $appId): bool => $appId > 0)
            ->unique()
            ->values();

        if ($key === '' || $appIds->isEmpty()) {
            return [];
        }

        $completed = [];

        foreach ($appIds->chunk(40) as $chunk) {
            try {
                $responses = Http::acceptJson()->pool(function (Pool $pool) use ($chunk, $key, $steamId): void {
                    foreach ($chunk as $appId) {
                        $pool->as((string) $appId)
                            ->connectTimeout(3)
                            ->timeout(8)
                            ->retry(2, 200, throw: false)
                            ->get($this->endpoint('ISteamUserStats/GetPlayerAchievements/v0001/'), [
                                'key' => $key,
                                'steamid' => $steamId,
                                'appid' => $appId,
                            ]);
                    }
                }, concurrency: 8);
            } catch (Throwable) {
                continue;
            }

            foreach ($responses as $appId => $response) {
                if (! $response instanceof Response || ! $response->successful()) {
                    continue;
                }

                $achievements = $response->json('playerstats.achievements');
                if ($response->json('playerstats.success') !== true
                    || ! is_array($achievements)
                    || $achievements === []) {
                    continue;
                }

                $allUnlocked = collect($achievements)->every(
                    fn (mixed $achievement): bool => is_array($achievement)
                        && (int) ($achievement['achieved'] ?? 0) === 1,
                );

                if ($allUnlocked) {
                    $completed[] = (int) $appId;
                }
            }
        }

        return $completed;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.steam.api_url'), '/').'/')
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->retry(2, 300, throw: false);
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('services.steam.api_url'), '/').'/'.ltrim($path, '/');
    }

    /** @throws SteamLibraryException */
    private function ensureSuccessful(Response $response): void
    {
        if ($response->successful()) {
            return;
        }

        if (in_array($response->status(), [401, 403], true)) {
            throw new SteamLibraryException(__('app.errors.steam_api_key_invalid'));
        }

        throw new SteamLibraryException(__('app.errors.steam_library_unavailable'));
    }
}
