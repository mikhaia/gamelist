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
            ->assertSee('Поиск игр')
            ->assertDontSee('Каталог игр')
            ->assertSee('Показать ещё 20')
            ->assertSee('data-catalog-browser-input', false)
            ->assertSee('href="'.route('games.show', CatalogGame::query()->oldest('id')->firstOrFail()).'"', false)
            ->assertDontSee('игр в локальном каталоге');
        $this->assertSame(20, substr_count($page->getContent(), 'data-catalog-browser-card'));

        $more = $this->actingAs($user)->getJson(route('catalog.results', [
            'gameList' => $list,
            'page' => 2,
        ]));
        $more->assertOk()->assertJsonPath('count', 1)->assertJsonPath('next_page', null);
        $this->assertSame(1, substr_count($more->json('html'), 'data-catalog-browser-card'));
    }

    public function test_global_search_is_public_and_keeps_add_buttons_visible_for_guests(): void
    {
        $catalogGame = CatalogGame::create([
            'hltb_id' => 51,
            'title' => 'Public Search Game',
            'normalized_title' => 'public search game',
        ]);

        $this->get(route('search.index'))
            ->assertOk()
            ->assertSee('Поиск игр')
            ->assertDontSee('Каталог игр')
            ->assertSee('href="'.route('games.show', $catalogGame).'"', false)
            ->assertSee('aria-label="Открыть страницу игры Public Search Game"', false)
            ->assertSee('data-catalog-list-picker', false)
            ->assertSee('data-catalog-browser-genre', false)
            ->assertSee('data-catalog-browser-platform', false)
            ->assertSee('Все жанры')
            ->assertSee('Все платформы')
            ->assertSee('data-login-url="'.route('login').'"', false)
            ->assertSee('href="'.route('search.index').'"', false);

        $this->getJson(route('search.results'))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertSee('Public Search Game');
    }

    public function test_search_filter_dropdowns_use_cached_rawg_metadata_and_keep_selected_values(): void
    {
        CatalogGame::create([
            'rawg_id' => 200,
            'title' => 'Filtered Game',
            'normalized_title' => 'filtered game',
            'genres' => ['RPG', 'Action'],
            'genre_slugs' => ['role-playing-games-rpg', 'action'],
            'platforms' => ['PC', 'PlayStation 5'],
            'platform_ids' => [4, 187],
        ]);

        $this->get(route('search.index', [
            'genre' => 'role-playing-games-rpg',
            'platform' => 4,
        ]))->assertOk()
            ->assertSee('<option value="role-playing-games-rpg" selected>RPG</option>', false)
            ->assertSee('<option value="4" selected>PC</option>', false)
            ->assertSeeInOrder(['Action', 'RPG'])
            ->assertSeeInOrder(['PC', 'PlayStation 5']);
    }

    public function test_global_search_opens_the_authenticated_users_lists_after_plus_click(): void
    {
        $user = User::factory()->create();
        $firstList = $user->gameLists()->create([
            'name' => 'Switch Games', 'slug' => 'switch-games', 'default_platform' => 'nintendo_switch',
        ]);
        $secondList = $user->gameLists()->create([
            'name' => 'Steam Games', 'slug' => 'steam-games', 'default_platform' => 'steam',
        ]);
        CatalogGame::create([
            'hltb_id' => 52,
            'title' => 'List Picker Game',
            'normalized_title' => 'list picker game',
        ]);

        $this->actingAs($user)->get(route('search.index'))
            ->assertOk()
            ->assertSee('data-catalog-list-dialog', false)
            ->assertSee('data-catalog-list-option', false)
            ->assertSee('Switch Games')
            ->assertSee('Steam Games')
            ->assertSee('data-add-url-template="'.route('catalog.add', [$firstList, 'CATALOG_GAME_ID']).'"', false)
            ->assertSee('data-add-url-template="'.route('catalog.add', [$secondList, 'CATALOG_GAME_ID']).'"', false)
            ->assertDontSee('data-login-url=', false);
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
            ->assertConflict()
            ->assertJsonPath('message', 'Такая игра уже добавлена.')
            ->assertJsonPath('duplicate.title', 'Hades II')
            ->assertJsonPath('duplicate.edit_url', route('games.edit', $list->games()->first()));

        $this->actingAs($user)->postJson(route('catalog.add', [$list, $catalogGame]), [
            'allow_duplicate' => true,
        ])->assertCreated();
        $this->assertSame(2, $list->games()->where('catalog_game_id', $catalogGame->id)->count());
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
