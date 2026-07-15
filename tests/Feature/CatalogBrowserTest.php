<?php

namespace Tests\Feature;

use App\Models\CatalogGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogBrowserTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_shows_twenty_cached_games_and_loads_the_next_page(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);

        foreach (range(1, 21) as $number) {
            CatalogGame::create([
                'hltb_id' => 1000 + $number,
                'title' => sprintf('Catalog Game %02d', $number),
                'normalized_title' => sprintf('catalog game %02d', $number),
            ]);
        }

        $page = $this->actingAs($user)->get(route('catalog.index', $list));
        $page->assertOk()
            ->assertSee('Каталог игр')
            ->assertSee('Показать ещё 20')
            ->assertSee('data-catalog-browser-input', false)
            ->assertDontSee('игр в локальном каталоге');
        $this->assertSame(20, substr_count($page->getContent(), 'data-catalog-browser-card'));

        $more = $this->actingAs($user)->getJson(route('catalog.results', [
            'gameList' => $list,
            'page' => 2,
        ]));
        $more->assertOk()->assertJsonPath('count', 1)->assertJsonPath('next_page', null);
        $this->assertSame(1, substr_count($more->json('html'), 'data-catalog-browser-card'));
    }

    public function test_catalog_search_returns_local_matches(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);
        CatalogGame::create([
            'hltb_id' => 77,
            'title' => 'Metroid Prime 4',
            'normalized_title' => 'metroid prime 4',
        ]);
        CatalogGame::create([
            'hltb_id' => 78,
            'title' => 'Hades II',
            'normalized_title' => 'hades ii',
        ]);

        $this->actingAs($user)->getJson(route('catalog.results', [
            'gameList' => $list,
            'q' => 'Metroid',
        ]))->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonMissingPath('total')
            ->assertSee('Metroid Prime 4')
            ->assertDontSee('Hades II');
    }

    public function test_game_can_be_added_from_catalog_without_page_reload(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);
        $catalogGame = CatalogGame::create([
            'hltb_id' => 99,
            'title' => 'Hades II',
            'normalized_title' => 'hades ii',
            'main_story_minutes' => 1200,
            'completionist_minutes' => 3600,
        ]);

        $this->actingAs($user)->postJson(route('catalog.add', [$list, $catalogGame]))
            ->assertCreated()
            ->assertJsonPath('message', 'Игра добавлена.');

        $this->assertDatabaseHas('games', [
            'game_list_id' => $list->id,
            'hltb_id' => 99,
            'title' => 'Hades II',
            'status' => 'want_to_play',
            'platform' => 'nintendo_switch',
            'main_story_minutes' => 1200,
            'completionist_minutes' => 3600,
        ]);

        $this->actingAs($user)->postJson(route('catalog.add', [$list, $catalogGame]))
            ->assertConflict();
    }

    public function test_another_user_cannot_open_or_add_to_catalog(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $list = $owner->gameLists()->create([
            'name' => 'Owner list', 'slug' => 'owner-list', 'default_platform' => 'pc',
        ]);
        $catalogGame = CatalogGame::create([
            'hltb_id' => 100,
            'title' => 'Private Add',
            'normalized_title' => 'private add',
        ]);

        $this->actingAs($other)->get(route('catalog.index', $list))->assertForbidden();
        $this->actingAs($other)->postJson(route('catalog.add', [$list, $catalogGame]))->assertForbidden();
    }
}
