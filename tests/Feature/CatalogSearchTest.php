<?php

namespace Tests\Feature;

use App\Contracts\GameCatalog;
use App\Models\CatalogGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_cached_games_are_rendered_without_waiting_for_external_catalog(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);
        CatalogGame::create([
            'hltb_id' => 123,
            'title' => 'Hades Cached',
            'normalized_title' => 'hades cached',
            'cover_url' => 'https://example.com/hades.jpg',
            'main_story_minutes' => 1200,
            'completionist_minutes' => 3000,
        ]);

        $this->actingAs($user)->get(route('games.create', ['gameList' => $list, 'q' => 'Hades']))
            ->assertOk()
            ->assertSee('Поиск')
            ->assertSee('Hades Cached')
            ->assertSee('Из кэша')
            ->assertSee('Показали локальные результаты');
    }

    public function test_fresh_catalog_results_are_cached_and_returned_as_html(): void
    {
        $this->mock(GameCatalog::class)
            ->shouldReceive('search')
            ->once()
            ->with('Hades', 20)
            ->andReturn([[
                'id' => 456,
                'title' => 'Hades II',
                'cover_url' => 'https://howlongtobeat.com/games/hades-ii.jpg',
                'main_story_minutes' => 1260,
                'main_extra_minutes' => 2100,
                'completionist_minutes' => 5400,
            ]]);

        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson(route('catalog.search', ['q' => 'Hades']));

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertSee('Hades II');
        $this->assertDatabaseHas('catalog_games', [
            'hltb_id' => 456,
            'title' => 'Hades II',
            'cover_url' => 'https://howlongtobeat.com/games/hades-ii.jpg',
            'main_story_minutes' => 1260,
            'completionist_minutes' => 5400,
        ]);
    }

    public function test_cached_endpoint_returns_results_without_external_catalog(): void
    {
        CatalogGame::create([
            'hltb_id' => 789,
            'title' => 'Metroid Prime 4',
            'normalized_title' => 'metroid prime 4',
            'cover_url' => 'https://example.com/metroid.jpg',
            'main_story_minutes' => 900,
            'completionist_minutes' => 1800,
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('catalog.cached', ['q' => 'Metroid']))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertSee('Metroid Prime 4');

        $this->assertStringContainsString('Из кэша', $response->json('html'));
    }

    public function test_hltb_result_merges_into_an_existing_rawg_only_game(): void
    {
        $rawgGame = CatalogGame::create([
            'rawg_id' => 3498,
            'rawg_slug' => 'grand-theft-auto-v',
            'title' => 'Grand Theft Auto V',
            'normalized_title' => 'grand theft auto v',
            'genres' => ['Action'],
            'genre_slugs' => ['action'],
        ]);

        $this->mock(GameCatalog::class)
            ->shouldReceive('search')
            ->once()
            ->with('Grand Theft Auto V', 20)
            ->andReturn([[
                'id' => 4064,
                'title' => 'Grand Theft Auto V',
                'cover_url' => 'https://howlongtobeat.com/games/gta-v.jpg',
                'main_story_minutes' => 1900,
                'main_extra_minutes' => null,
                'completionist_minutes' => 5000,
            ]]);

        $this->getJson(route('catalog.search', ['q' => 'Grand Theft Auto V']))->assertOk();

        $rawgGame->refresh();
        $this->assertSame(4064, $rawgGame->hltb_id);
        $this->assertSame(3498, $rawgGame->rawg_id);
        $this->assertSame(['action'], $rawgGame->genre_slugs);
        $this->assertDatabaseCount('catalog_games', 1);
    }
}
