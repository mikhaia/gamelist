<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Enums\Platform;
use App\Models\CatalogGame;
use App\Models\GameList;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class SteamLibraryImporter
{
    private const LIST_COVER = 'images/steam/list-cover.webp';

    public function __construct(
        private readonly SteamWebApi $steam,
        private readonly GameTitleNormalizer $titles,
        private readonly AchievementService $achievements,
    ) {}

    /** @return array{list: GameList, imported: int, created: bool} */
    public function import(User $user): array
    {
        $existing = $user->gameLists()->where('slug', 'steam')->first();
        if ($existing) {
            return [
                'list' => $existing,
                'imported' => $existing->games()->count(),
                'created' => false,
            ];
        }

        $steamGames = collect($this->steam->ownedGames((string) $user->steam_id))
            ->map(fn (array $game): array => $game + [
                'normalized_title' => $this->titles->normalize($game['name']),
                'cover_url' => $this->coverUrl($game['appid']),
            ])
            ->filter(fn (array $game): bool => $game['normalized_title'] !== '')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $coverPath = $this->storeListCover();

        try {
            $gameList = DB::transaction(function () use ($user, $steamGames, $coverPath): GameList {
                $gameList = $user->gameLists()->create([
                    'name' => 'Игры из Steam',
                    'slug' => 'steam',
                    'description' => 'Мои игры из Steam',
                    'cover_path' => $coverPath,
                    'default_platform' => Platform::Steam->value,
                    'available_statuses' => [
                        GameStatus::WantToPlay->value,
                        GameStatus::Playing->value,
                        GameStatus::Completed->value,
                        GameStatus::Completed100->value,
                    ],
                    'is_public' => false,
                ]);

                if ($steamGames->isEmpty()) {
                    return $gameList;
                }

                $catalogGames = $this->catalogGames($steamGames);
                $now = now();
                $rows = $steamGames->map(function (array $steamGame, int $index) use ($gameList, $catalogGames, $now): array {
                    $catalogGame = $catalogGames->get((string) $steamGame['appid']);

                    return [
                        'game_list_id' => $gameList->id,
                        'catalog_game_id' => $catalogGame?->id,
                        'title' => $steamGame['name'],
                        'normalized_title' => $steamGame['normalized_title'],
                        'status' => GameStatus::WantToPlay->value,
                        'platform' => Platform::Steam->value,
                        'source_cover_url' => $catalogGame?->cover_url ?: $steamGame['cover_url'],
                        'sort_order' => $index,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                });

                $rows->chunk(500)->each(fn (Collection $chunk) => DB::table('games')->insert($chunk->all()));

                $gameList->games()
                    ->select(['id', 'created_at'])
                    ->reorder()
                    ->orderBy('id')
                    ->chunkById(500, function ($games): void {
                        DB::table('game_status_events')->insert(
                            $games->map(fn ($game): array => [
                                'game_id' => $game->id,
                                'status' => GameStatus::WantToPlay->value,
                                'changed_at' => $game->created_at,
                            ])->all(),
                        );
                    });

                return $gameList;
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($coverPath);

            if ($exception instanceof QueryException) {
                $existing = $user->gameLists()->where('slug', 'steam')->first();
                if ($existing) {
                    return [
                        'list' => $existing,
                        'imported' => $existing->games()->count(),
                        'created' => false,
                    ];
                }
            }

            throw $exception;
        }

        $this->achievements->evaluate($user);

        return [
            'list' => $gameList,
            'imported' => $steamGames->count(),
            'created' => true,
        ];
    }

    /**
     * @param  Collection<int, array{appid: int, name: string, normalized_title: string, cover_url: string}>  $steamGames
     * @return Collection<string, CatalogGame>
     */
    private function catalogGames(Collection $steamGames): Collection
    {
        $catalogGames = collect();

        $steamGames->pluck('appid')->chunk(500)->each(function (Collection $ids) use ($catalogGames): void {
            CatalogGame::query()->whereIn('steam_id', $ids->all())->get()
                ->each(fn (CatalogGame $game) => $catalogGames->put((string) $game->steam_id, $game));
        });

        $missing = $steamGames->reject(fn (array $game): bool => $catalogGames->has((string) $game['appid']));
        $catalogByTitle = collect();

        $missing->pluck('normalized_title')->unique()->chunk(500)->each(function (Collection $titles) use ($catalogByTitle): void {
            CatalogGame::query()
                ->whereNull('steam_id')
                ->whereIn('normalized_title', $titles->all())
                ->orderBy('id')
                ->get()
                ->each(function (CatalogGame $game) use ($catalogByTitle): void {
                    if (! $catalogByTitle->has($game->normalized_title)) {
                        $catalogByTitle->put($game->normalized_title, $game);
                    }
                });
        });

        $newRows = collect();
        $now = now();

        foreach ($missing as $steamGame) {
            $catalogGame = $catalogByTitle->pull($steamGame['normalized_title']);

            if ($catalogGame) {
                $catalogGame->forceFill([
                    'steam_id' => (string) $steamGame['appid'],
                    'cover_url' => $catalogGame->cover_url ?: $steamGame['cover_url'],
                    'platforms' => $catalogGame->platforms ?: ['PC'],
                    'platform_ids' => $catalogGame->platform_ids ?: [4],
                ])->saveQuietly();
                $catalogGames->put((string) $steamGame['appid'], $catalogGame);

                continue;
            }

            $newRows->push([
                'title' => $steamGame['name'],
                'normalized_title' => $steamGame['normalized_title'],
                'cover_url' => $steamGame['cover_url'],
                'platforms' => json_encode(['PC'], JSON_THROW_ON_ERROR),
                'platform_ids' => json_encode([4], JSON_THROW_ON_ERROR),
                'steam_id' => (string) $steamGame['appid'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $newRows->chunk(500)->each(fn (Collection $chunk) => DB::table('catalog_games')->insert($chunk->all()));

        $unresolvedIds = $steamGames->pluck('appid')
            ->map(fn (int $id): string => (string) $id)
            ->reject(fn (string $id): bool => $catalogGames->has($id));
        $unresolvedIds->chunk(500)->each(function (Collection $ids) use ($catalogGames): void {
            CatalogGame::query()->whereIn('steam_id', $ids->all())->get()
                ->each(fn (CatalogGame $game) => $catalogGames->put((string) $game->steam_id, $game));
        });

        return $catalogGames;
    }

    private function storeListCover(): string
    {
        $source = public_path(self::LIST_COVER);
        $bytes = is_file($source) ? file_get_contents($source) : false;
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('Steam list cover asset is missing.');
        }

        $path = 'list-covers/'.Str::uuid().'.webp';
        if (! Storage::disk('public')->put($path, $bytes)) {
            throw new RuntimeException('Could not store the Steam list cover.');
        }

        return $path;
    }

    private function coverUrl(int $appId): string
    {
        return "https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/{$appId}/library_600x900.jpg";
    }
}
