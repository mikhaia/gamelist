<?php

namespace Tests\Feature;

use App\Models\CatalogGame;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RawgCatalogEnrichmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_page_enriches_catalog_game_and_displays_rawg_metadata(): void
    {
        config([
            'services.rawg.key' => 'test-key',
            'services.rawg.base_url' => 'https://api.rawg.io/api',
            'services.rawg.sync_ttl_days' => 30,
        ]);

        Http::preventStrayRequests();
        Http::fake([
            'https://api.rawg.io/api/games?*' => Http::response([
                'results' => [
                    ['id' => 49, 'name' => 'Persona 5'],
                    [
                        'id' => 339958,
                        'name' => 'Persona 5 Royal',
                        'slug' => 'persona-5-royal',
                        'background_image' => 'https://media.rawg.io/background.jpg',
                        'genres' => [
                            ['name' => 'Adventure', 'slug' => 'adventure'],
                            ['name' => 'RPG', 'slug' => 'role-playing-games-rpg'],
                            ['name' => 'RPG', 'slug' => 'role-playing-games-rpg'],
                        ],
                        'esrb_rating' => [
                            'id' => 3,
                            'slug' => 'teen',
                            'name' => 'Teen',
                        ],
                        'tags' => [['name' => 'Singleplayer']],
                        'platforms' => [
                            ['platform' => ['id' => 4, 'name' => 'PC']],
                            ['platform' => ['id' => 187, 'name' => 'PlayStation 5']],
                        ],
                        'short_screenshots' => [
                            ['image' => 'https://media.rawg.io/background.jpg'],
                            ['image' => 'https://media.rawg.io/fallback.jpg'],
                        ],
                    ],
                ],
            ]),
            'https://api.rawg.io/api/games/339958/screenshots?*' => Http::response([
                'results' => [
                    ['image' => 'https://media.rawg.io/background.jpg', 'is_deleted' => false],
                    ['image' => 'https://media.rawg.io/screenshot-1.jpg', 'is_deleted' => false],
                    ['image' => 'https://media.rawg.io/screenshot-1.jpg', 'is_deleted' => false],
                    ['image' => 'https://media.rawg.io/deleted.jpg', 'is_deleted' => true],
                    ['image' => 'http://media.rawg.io/insecure.jpg', 'is_deleted' => false],
                ],
            ]),
            'https://api.rawg.io/api/games/339958/stores?*' => Http::response([
                'results' => [
                    ['store_id' => 1, 'url' => 'https://example.com/app/999/'],
                    ['store_id' => 1, 'url' => 'https://store.steampowered.com/app/1687950/Persona_5_Royal/'],
                ],
            ]),
        ]);

        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 9001,
            'title' => 'Persona 5 Royal',
            'normalized_title' => 'persona 5 royal',
        ]);

        $this->get(route('games.show', $catalogGame))
            ->assertOk()
            ->assertDontSee('https://media.rawg.io/background.jpg', false)
            ->assertSee('data-rawg-metadata', false)
            ->assertSee('Adventure')
            ->assertSee('RPG')
            ->assertSee(route('search.index', ['genre' => 'role-playing-games-rpg', 'genre_name' => 'RPG']))
            ->assertSee('PC')
            ->assertSee('PlayStation 5')
            ->assertSee(route('search.index', ['platform' => 4, 'platform_name' => 'PC']))
            ->assertSee('data-age-rating="13+"', false)
            ->assertDontSee('Singleplayer')
            ->assertSee('data-game-screenshots', false)
            ->assertSee('data-screenshot-open', false)
            ->assertSee('data-screenshot-modal', false)
            ->assertSeeInOrder(['</main>', 'data-screenshot-modal'], false)
            ->assertSee('https://media.rawg.io/screenshot-1.jpg', false)
            ->assertDontSee('https://media.rawg.io/deleted.jpg', false)
            ->assertDontSee('http://media.rawg.io/insecure.jpg', false)
            ->assertSee('photo_library', false)
            ->assertSee('Скриншоты')
            ->assertSee('href="https://rawg.io/"', false)
            ->assertSee('href="https://howlongtobeat.com/"', false)
            ->assertDontSee('data-rawg-attribution', false);

        $catalogGame->refresh();

        $this->assertSame(339958, $catalogGame->rawg_id);
        $this->assertSame('persona-5-royal', $catalogGame->rawg_slug);
        $this->assertSame(['https://media.rawg.io/screenshot-1.jpg'], $catalogGame->screenshots);
        $this->assertSame(['Adventure', 'RPG'], $catalogGame->genres);
        $this->assertSame(['adventure', 'role-playing-games-rpg'], $catalogGame->genre_slugs);
        $this->assertSame('Teen', $catalogGame->age_rating);
        $this->assertSame('13+', $catalogGame->ageRatingLabel());
        $this->assertSame(['PC', 'PlayStation 5'], $catalogGame->platforms);
        $this->assertSame([4, 187], $catalogGame->platform_ids);
        $this->assertSame('1687950', $catalogGame->steam_id);
        $this->assertNotNull($catalogGame->rawg_synced_at);
        $this->assertFalse(Schema::hasColumn('catalog_games', 'background'));

        $this->get(route('games.show', $catalogGame))->assertOk();
        Http::assertSentCount(3);
    }

    public function test_game_page_does_not_call_rawg_without_an_api_key(): void
    {
        config(['services.rawg.key' => null]);
        Http::preventStrayRequests();

        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 9002,
            'title' => 'Local Game',
            'normalized_title' => 'local game',
        ]);

        $this->get(route('games.show', $catalogGame))
            ->assertOk()
            ->assertSee('Local Game');

        Http::assertNothingSent();
    }

    public function test_rawg_failure_does_not_break_the_game_page(): void
    {
        config([
            'services.rawg.key' => 'test-key',
            'services.rawg.base_url' => 'https://api.rawg.io/api',
        ]);

        Http::fake([
            'https://api.rawg.io/api/games?*' => Http::response(['detail' => 'Unavailable'], 503),
        ]);

        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 9003,
            'title' => 'Offline Game',
            'normalized_title' => 'offline game',
        ]);

        $this->get(route('games.show', $catalogGame))
            ->assertOk()
            ->assertSee('Offline Game');

        $this->assertNull($catalogGame->refresh()->rawg_id);
    }
}
