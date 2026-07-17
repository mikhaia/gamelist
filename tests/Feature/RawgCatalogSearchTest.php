<?php

namespace Tests\Feature;

use App\Models\CatalogGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RawgCatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::clear();
        config([
            'services.rawg.key' => 'test-key',
            'services.rawg.base_url' => 'https://api.rawg.io/api',
            'services.rawg.search_ttl_hours' => 6,
        ]);
        Http::preventStrayRequests();
    }

    public function test_genre_search_merges_rawg_metadata_into_hltb_game_and_uses_ttl_cache(): void
    {
        $catalogGame = CatalogGame::create([
            'hltb_id' => 456,
            'title' => 'Hades',
            'normalized_title' => 'hades',
            'main_story_minutes' => 1260,
        ]);
        Http::fake([
            'https://api.rawg.io/api/games?*' => Http::response([
                'results' => [[
                    'id' => 12020,
                    'slug' => 'hades-2018',
                    'name' => 'Hades',
                    'background_image' => 'https://media.rawg.io/hades.jpg',
                    'added' => 15432,
                    'genres' => [
                        ['name' => 'Action', 'slug' => 'action'],
                        ['name' => 'Indie', 'slug' => 'indie'],
                    ],
                    'platforms' => [
                        ['platform' => ['id' => 4, 'name' => 'PC']],
                    ],
                    'esrb_rating' => ['name' => 'Teen'],
                    'short_screenshots' => [
                        ['image' => 'https://media.rawg.io/hades.jpg'],
                        ['image' => 'https://media.rawg.io/hades-shot.jpg'],
                    ],
                ]],
            ]),
        ]);

        $url = route('catalog.rawg-search', [
            'genre' => 'action',
            'genre_name' => 'Action',
        ]);
        $this->getJson($url)->assertOk()->assertJsonPath('count', 1);
        $this->getJson($url)->assertOk()->assertJsonPath('count', 1);

        $catalogGame->refresh();
        $this->assertSame(12020, $catalogGame->rawg_id);
        $this->assertSame(456, $catalogGame->hltb_id);
        $this->assertSame(['Action', 'Indie'], $catalogGame->genres);
        $this->assertSame(['action', 'indie'], $catalogGame->genre_slugs);
        $this->assertSame(['PC'], $catalogGame->platforms);
        $this->assertSame([4], $catalogGame->platform_ids);
        $this->assertSame(['https://media.rawg.io/hades-shot.jpg'], $catalogGame->screenshots);
        $this->assertSame(15432, $catalogGame->rawg_added);
        $this->assertSame(1260, $catalogGame->main_story_minutes);
        $this->assertNull($catalogGame->rawg_synced_at);
        $this->assertDatabaseCount('catalog_games', 1);

        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => str_starts_with($request->url(), 'https://api.rawg.io/api/games?')
            && $request['genres'] === 'action'
            && $request['ordering'] === '-added'
            && (int) $request['page_size'] === 40);
    }

    public function test_platform_search_caches_rawg_only_game_locally_and_it_can_be_added_to_a_list(): void
    {
        Http::fake([
            'https://api.rawg.io/api/games?*' => Http::response([
                'results' => [[
                    'id' => 41494,
                    'slug' => 'cyberpunk-2077',
                    'name' => 'Cyberpunk 2077',
                    'background_image' => 'https://media.rawg.io/cyberpunk.jpg',
                    'added' => 20000,
                    'genres' => [['name' => 'RPG', 'slug' => 'role-playing-games-rpg']],
                    'platforms' => [['platform' => ['id' => 4, 'name' => 'PC']]],
                    'short_screenshots' => [],
                ]],
            ]),
        ]);

        $this->getJson(route('catalog.rawg-search', [
            'platform' => 4,
            'platform_name' => 'PC',
        ]))->assertOk()->assertJsonPath('count', 1);

        $catalogGame = CatalogGame::query()->sole();
        $this->assertNull($catalogGame->hltb_id);
        $this->assertSame(41494, $catalogGame->rawg_id);

        $this->getJson(route('search.results', ['platform' => 4, 'platform_name' => 'PC']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertSee('Cyberpunk 2077');

        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'PC', 'slug' => 'pc', 'default_platform' => 'pc',
        ]);

        $this->actingAs($user)->postJson(route('catalog.add', [$list, $catalogGame]))
            ->assertCreated();
        $this->assertDatabaseHas('games', [
            'catalog_game_id' => $catalogGame->id,
            'hltb_id' => null,
            'title' => 'Cyberpunk 2077',
        ]);

        Http::assertSent(fn ($request): bool => (int) $request['platforms'] === 4);
    }

    public function test_local_results_can_be_filtered_by_genre_and_platform_together(): void
    {
        CatalogGame::create([
            'rawg_id' => 1,
            'title' => 'Matching Game',
            'normalized_title' => 'matching game',
            'genre_slugs' => ['action'],
            'platform_ids' => [4, 187],
            'rawg_added' => 100,
        ]);
        CatalogGame::create([
            'rawg_id' => 2,
            'title' => 'Wrong Platform',
            'normalized_title' => 'wrong platform',
            'genre_slugs' => ['action'],
            'platform_ids' => [187],
            'rawg_added' => 200,
        ]);

        $this->getJson(route('search.results', [
            'genre' => 'action',
            'genre_name' => 'Action',
            'platform' => 4,
            'platform_name' => 'PC',
        ]))->assertOk()
            ->assertJsonPath('count', 1)
            ->assertSee('Matching Game')
            ->assertDontSee('Wrong Platform');
    }

    public function test_filter_refresh_does_not_replace_full_detail_screenshots_with_short_ones(): void
    {
        $catalogGame = CatalogGame::create([
            'rawg_id' => 12020,
            'rawg_slug' => 'hades-2018',
            'title' => 'Hades',
            'normalized_title' => 'hades',
            'screenshots' => [
                'https://media.rawg.io/full-1.jpg',
                'https://media.rawg.io/full-2.jpg',
            ],
            'genre_slugs' => ['action'],
            'rawg_synced_at' => now(),
        ]);
        Http::fake([
            'https://api.rawg.io/api/games?*' => Http::response([
                'results' => [[
                    'id' => 12020,
                    'slug' => 'hades-2018',
                    'name' => 'Hades',
                    'genres' => [['name' => 'Action', 'slug' => 'action']],
                    'platforms' => [],
                    'short_screenshots' => [['image' => 'https://media.rawg.io/short.jpg']],
                ]],
            ]),
        ]);

        $this->getJson(route('catalog.rawg-search', ['genre' => 'action']))->assertOk();

        $this->assertSame([
            'https://media.rawg.io/full-1.jpg',
            'https://media.rawg.io/full-2.jpg',
        ], $catalogGame->refresh()->screenshots);
    }
}
