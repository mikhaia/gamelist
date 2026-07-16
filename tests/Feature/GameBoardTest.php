<?php

namespace Tests\Feature;

use App\Models\CatalogGame;
use App\Models\GameList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_can_use_selected_statuses_as_board_columns(): void
    {
        $user = User::factory()->create(['login' => 'chrono']);

        $this->actingAs($user)->post(route('lists.store'), [
            'name' => 'Консольные игры',
            'slug' => 'console-games',
            'default_platform' => 'playstation',
            'available_statuses' => ['want_to_play', 'playing', 'completed'],
            'is_public' => '1',
        ])->assertRedirect();

        $gameList = GameList::query()->where('slug', 'console-games')->firstOrFail();
        $this->assertSame(['want_to_play', 'playing', 'completed'], $gameList->available_statuses);

        $gameList->games()->create([
            'title' => 'Astro Bot',
            'normalized_title' => 'astro bot',
            'status' => 'playing',
            'platform' => 'playstation',
        ]);
        $gameList->games()->create([
            'title' => 'God of War',
            'normalized_title' => 'god of war',
            'status' => 'completed',
            'platform' => 'playstation',
        ]);

        $this->actingAs($user)
            ->patch(route('lists.display', $gameList), ['display_mode' => 'board'])
            ->assertRedirect();

        $ownerPage = $this->actingAs($user)->get(route('lists.show', $gameList));
        $ownerPage->assertOk()
            ->assertSee('Доска')
            ->assertSee('data-game-board', false)
            ->assertSee('data-game-status-form', false)
            ->assertSee('data-game-status-select', false)
            ->assertSee('data-board-games', false)
            ->assertSee('data-board-count', false)
            ->assertSee('Astro Bot')
            ->assertSee('God of War')
            ->assertDontSee('Установлена')
            ->assertDontSee('Брошена');
        $this->assertSame(3, substr_count($ownerPage->getContent(), 'data-board-status'));

        $this->get(route('public.lists.show', ['login' => 'chrono', 'slug' => 'console-games']))
            ->assertOk()
            ->assertSee('data-game-board', false)
            ->assertSee('Astro Bot');
    }

    public function test_used_status_cannot_be_disabled(): void
    {
        $user = User::factory()->create();
        $gameList = $user->gameLists()->create([
            'name' => 'Games',
            'slug' => 'games',
            'default_platform' => 'pc',
            'available_statuses' => ['want_to_play', 'playing'],
        ]);
        $gameList->games()->create([
            'title' => 'Playing Game',
            'normalized_title' => 'playing game',
            'status' => 'playing',
            'platform' => 'pc',
        ]);

        $this->actingAs($user)->put(route('lists.update', $gameList), [
            'name' => 'Games',
            'slug' => 'games',
            'default_platform' => 'pc',
            'available_statuses' => ['want_to_play'],
        ])->assertSessionHasErrors('available_statuses');

        $this->assertSame(['want_to_play', 'playing'], $gameList->fresh()->available_statuses);
    }

    public function test_games_can_only_receive_statuses_enabled_for_the_list(): void
    {
        $user = User::factory()->create();
        $gameList = $user->gameLists()->create([
            'name' => 'Games',
            'slug' => 'games',
            'default_platform' => 'xbox',
            'available_statuses' => ['playing', 'completed'],
        ]);
        $game = $gameList->games()->create([
            'title' => 'Forza Horizon 5',
            'normalized_title' => 'forza horizon 5',
            'status' => 'playing',
            'platform' => 'xbox',
        ]);

        $this->actingAs($user)
            ->get(route('games.create', $gameList))
            ->assertOk()
            ->assertSee('Играю')
            ->assertSee('Пройдена')
            ->assertDontSee('Хочу сыграть')
            ->assertDontSee('Установлена')
            ->assertDontSee('Брошена');

        $this->actingAs($user)
            ->patch(route('games.status', $game), ['status' => 'dropped'])
            ->assertSessionHasErrors('status');

        $this->assertSame('playing', $game->fresh()->status->value);
    }

    public function test_catalog_quick_add_uses_first_enabled_status(): void
    {
        $user = User::factory()->create();
        $gameList = $user->gameLists()->create([
            'name' => 'Games',
            'slug' => 'games',
            'default_platform' => 'pc',
            'available_statuses' => ['installed', 'playing'],
        ]);
        $catalogGame = CatalogGame::create([
            'hltb_id' => 500,
            'title' => 'Quick Game',
            'normalized_title' => 'quick game',
        ]);

        $this->actingAs($user)
            ->postJson(route('catalog.add', [$gameList, $catalogGame]))
            ->assertCreated();

        $this->assertDatabaseHas('games', [
            'game_list_id' => $gameList->id,
            'status' => 'installed',
        ]);
    }
}
