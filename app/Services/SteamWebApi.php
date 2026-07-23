<?php

namespace App\Services;

use App\Exceptions\SteamLibraryException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class SteamWebApi
{
    /**
     * @return array<int, array{appid: int, name: string}>
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
            ])
            ->unique('appid')
            ->values()
            ->all();
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.steam.api_url'), '/').'/')
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->retry(2, 300, throw: false);
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
