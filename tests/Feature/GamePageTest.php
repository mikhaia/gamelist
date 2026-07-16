<?php

namespace Tests\Feature;

use App\Enums\GameStatus;
use App\Models\CatalogGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_page_shows_cover_and_status_counts_without_status_heading(): void
    {
        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 9001,
            'title' => 'Hades II',
            'normalized_title' => 'hades ii',
            'cover_url' => 'https://images.example.com/hades-ii.jpg',
            'main_story_minutes' => 1200,
            'completionist_minutes' => 3600,
        ]);

        foreach ([
            GameStatus::WantToPlay,
            GameStatus::WantToPlay,
            GameStatus::Playing,
            GameStatus::Completed,
        ] as $index => $status) {
            $user = User::factory()->create(['login' => 'player_'.$index]);
            $list = $user->gameLists()->create([
                'name' => 'Games '.$index,
                'slug' => 'games-'.$index,
                'default_platform' => 'pc',
            ]);
            $list->games()->create([
                'catalog_game_id' => $catalogGame->id,
                'title' => 'Hades II',
                'normalized_title' => 'hades ii',
                'status' => $status,
                'platform' => 'pc',
                'hltb_id' => $catalogGame->hltb_id,
            ]);
        }

        $this->get(route('games.show', $catalogGame))
            ->assertOk()
            ->assertSee('data-game-page="'.$catalogGame->id.'"', false)
            ->assertSee('https://images.example.com/hades-ii.jpg', false)
            ->assertSee('4 добавления')
            ->assertSee('panel p-4 mt-6', false)
            ->assertDontSee('Добавления по статусам')
            ->assertSee('data-status-count="want_to_play" data-count="2"', false)
            ->assertSee('data-status-count="installed" data-count="0"', false)
            ->assertSee('data-status-count="playing" data-count="1"', false)
            ->assertSee('data-status-count="completed" data-count="1"', false)
            ->assertSee('data-status-count="dropped" data-count="0"', false)
            ->assertDontSee('Страница игры');
    }

    public function test_only_catalog_games_link_to_a_page_in_every_list_mode(): void
    {
        $user = User::factory()->create(['login' => 'chrono']);
        $list = $user->gameLists()->create([
            'name' => 'Games',
            'slug' => 'games',
            'default_platform' => 'pc',
            'is_public' => true,
        ]);
        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 314,
            'title' => 'Control Ultimate Edition',
            'normalized_title' => 'control ultimate edition',
        ]);
        $linkedGame = $list->games()->create([
            'title' => $catalogGame->title,
            'normalized_title' => $catalogGame->normalized_title,
            'status' => 'playing',
            'platform' => 'pc',
            'hltb_id' => $catalogGame->hltb_id,
        ]);
        $manualGame = $list->games()->create([
            'title' => 'Manual Game',
            'normalized_title' => 'manual game',
            'status' => 'want_to_play',
            'platform' => 'pc',
        ]);

        $this->assertSame($catalogGame->id, $linkedGame->catalog_game_id);
        $this->assertNull($manualGame->catalog_game_id);
        $this->assertDatabaseMissing('catalog_games', ['normalized_title' => 'manual game']);
        $gameUrl = route('games.show', $catalogGame);

        foreach (['cards', 'compact', 'board'] as $mode) {
            $list->update(['display_mode' => $mode]);
            $this->actingAs($user)->get(route('lists.show', $list))
                ->assertOk()
                ->assertSee('href="'.$gameUrl.'"', false)
                ->assertSee('aria-label="Открыть страницу игры Control Ultimate Edition"', false)
                ->assertDontSee('aria-label="Открыть страницу игры Manual Game"', false);
        }

        $this->get(route('public.lists.show', [$user->login, $list->slug]))
            ->assertOk()
            ->assertSee('href="'.$gameUrl.'"', false)
            ->assertDontSee('aria-label="Открыть страницу игры Manual Game"', false);
    }

    public function test_authenticated_user_can_add_catalog_game_to_owned_list(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Steam', 'slug' => 'steam', 'default_platform' => 'steam',
        ]);
        $otherList = $other->gameLists()->create([
            'name' => 'Other', 'slug' => 'other', 'default_platform' => 'pc',
        ]);
        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 42,
            'title' => 'Portal 2',
            'normalized_title' => 'portal 2',
        ]);

        $this->actingAs($user)->post(route('game-library.store', $catalogGame), [
            'game_list_id' => $list->id,
        ])->assertRedirect(route('games.show', $catalogGame));

        $this->assertDatabaseHas('games', [
            'game_list_id' => $list->id,
            'catalog_game_id' => $catalogGame->id,
            'title' => 'Portal 2',
            'platform' => 'steam',
            'status' => 'want_to_play',
        ]);

        $this->actingAs($user)->post(route('game-library.store', $catalogGame), [
            'game_list_id' => $list->id,
        ])->assertSessionHasErrors('game_list_id');
        $this->actingAs($user)->post(route('game-library.store', $catalogGame), [
            'game_list_id' => $otherList->id,
        ])->assertSessionHasErrors('game_list_id');
    }

    public function test_users_can_rate_review_update_and_delete_their_opinion(): void
    {
        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 77,
            'title' => 'Disco Elysium',
            'normalized_title' => 'disco elysium',
        ]);
        $first = User::factory()->create(['login' => 'first_player']);
        $second = User::factory()->create(['login' => 'second_player']);

        $this->actingAs($first)->put(route('game-reviews.update', $catalogGame), [
            'rating' => 8,
            'body' => '**Отличная** ролевая игра.',
        ])->assertRedirect(route('games.show', $catalogGame));
        $this->actingAs($second)->put(route('game-reviews.update', $catalogGame), [
            'rating' => 10,
            'body' => "<script>alert('xss')</script>\n**Шедевр**.",
        ])->assertRedirect(route('games.show', $catalogGame));

        $this->assertDatabaseCount('game_reviews', 2);
        $this->get(route('games.show', $catalogGame))
            ->assertOk()
            ->assertSee('9,0 / 10')
            ->assertSee('<strong>Отличная</strong>', false)
            ->assertSee('<strong>Шедевр</strong>', false)
            ->assertDontSee('<script>', false);

        $this->actingAs($first)->put(route('game-reviews.update', $catalogGame), [
            'rating' => 11,
            'body' => 'Too much',
        ])->assertSessionHasErrors('rating');

        $this->actingAs($first)->delete(route('game-reviews.destroy', $catalogGame))
            ->assertRedirect(route('games.show', $catalogGame));
        $this->assertDatabaseCount('game_reviews', 1);
    }

    public function test_markdown_preview_is_rendered_safely_without_page_reload(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('game-reviews.preview'), [
            'body' => "# Заголовок\n\n<script>alert('xss')</script>\n\n**Текст**",
        ])->assertOk()
            ->assertJsonPath('html', fn (string $html): bool => str_contains($html, '<h1>Заголовок</h1>')
                && str_contains($html, '<strong>Текст</strong>')
                && ! str_contains($html, '<script>'));
    }
}
