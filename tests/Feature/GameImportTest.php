<?php

namespace Tests\Feature;

use App\Contracts\GameCatalog;
use App\Models\CatalogGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preview_strips_markdown_and_marks_duplicates(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);
        $list->games()->create([
            'title' => 'Hades II', 'normalized_title' => 'hades ii', 'status' => 'playing', 'platform' => 'nintendo_switch',
        ]);
        $exactMatch = CatalogGame::query()->create([
            'hltb_id' => 101,
            'title' => 'Metroid Prime 4',
            'normalized_title' => 'metroid prime 4',
            'cover_url' => 'https://images.example.com/metroid-prime-4.jpg',
        ]);
        CatalogGame::query()->create([
            'hltb_id' => 102,
            'title' => 'Metroid Prime Remastered',
            'normalized_title' => 'metroid prime remastered',
            'cover_url' => 'https://images.example.com/metroid-prime-remastered.jpg',
        ]);
        $this->mock(GameCatalog::class)->shouldNotReceive('search');

        $this->actingAs($user)->post(route('imports.preview', $list), [
            'games_text' => "- [ ] Metroid Prime 4\n1. Hades II\n- [x] Metroid Prime 4",
        ])->assertOk()
            ->assertSee('Metroid Prime 4')
            ->assertSee('Уже в списке')
            ->assertSee('Повтор строки')
            ->assertSee('Связать с игрой из каталога')
            ->assertSee('data-import-catalog-suggestions', false)
            ->assertSee('class="mt-3 flex gap-3 overflow-x-auto', false)
            ->assertSee('name="items[0][catalog_game_id]" value="'.$exactMatch->id.'" class="peer sr-only" checked', false)
            ->assertSee('https://images.example.com/metroid-prime-4.jpg')
            ->assertSee('Metroid Prime Remastered');
    }

    public function test_import_preview_falls_back_to_external_catalog_when_local_catalog_has_no_matches(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'PC', 'slug' => 'pc', 'default_platform' => 'pc',
        ]);
        $this->mock(GameCatalog::class)
            ->shouldReceive('search')
            ->once()
            ->with('Chronicles of Evershade', 6)
            ->andReturn([[
                'id' => 909,
                'title' => 'Chronicles of Evershade',
                'cover_url' => 'https://images.example.com/chronicles-of-evershade.jpg',
                'main_story_minutes' => 1200,
                'main_extra_minutes' => 1800,
                'completionist_minutes' => 2400,
            ]]);

        $response = $this->actingAs($user)->post(route('imports.preview', $list), [
            'games_text' => 'Chronicles of Evershade',
        ]);

        $catalogGame = CatalogGame::query()->where('hltb_id', 909)->sole();

        $response->assertOk()
            ->assertSee('Chronicles of Evershade')
            ->assertSee('https://images.example.com/chronicles-of-evershade.jpg')
            ->assertSee('name="items[0][catalog_game_id]" value="'.$catalogGame->id.'" class="peer sr-only" checked', false);
        $this->assertDatabaseHas('catalog_games', [
            'hltb_id' => 909,
            'title' => 'Chronicles of Evershade',
            'main_story_minutes' => 1200,
            'completionist_minutes' => 2400,
        ]);
    }

    public function test_selected_games_are_imported_without_duplicates(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);

        $this->actingAs($user)->post(route('imports.store', $list), [
            'items' => [
                ['selected' => '1', 'title' => 'Metroid Prime 4', 'catalog_game_id' => null],
                ['selected' => '1', 'title' => 'metroid prime 4', 'catalog_game_id' => null],
                ['selected' => '1', 'title' => 'Hades II', 'catalog_game_id' => null],
            ],
            'status' => 'want_to_play',
            'platform' => 'nintendo_switch',
        ])->assertRedirect(route('lists.show', $list));

        $this->assertDatabaseCount('games', 2);
    }

    public function test_selected_catalog_match_uses_canonical_game_data(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'PC', 'slug' => 'pc', 'default_platform' => 'pc',
        ]);
        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 303,
            'title' => 'The Witcher 3: Wild Hunt',
            'normalized_title' => 'the witcher 3: wild hunt',
            'main_story_minutes' => 3100,
            'completionist_minutes' => 10300,
        ]);

        $this->actingAs($user)->post(route('imports.store', $list), [
            'items' => [[
                'selected' => '1',
                'title' => 'witcher 3',
                'catalog_game_id' => $catalogGame->id,
            ]],
            'status' => 'playing',
            'platform' => 'steam',
        ])->assertRedirect(route('lists.show', $list));

        $this->assertDatabaseHas('games', [
            'game_list_id' => $list->id,
            'catalog_game_id' => $catalogGame->id,
            'title' => 'The Witcher 3: Wild Hunt',
            'normalized_title' => 'the witcher 3: wild hunt',
            'hltb_id' => 303,
            'status' => 'playing',
            'platform' => 'steam',
            'main_story_minutes' => 3100,
            'completionist_minutes' => 10300,
        ]);
    }
}
