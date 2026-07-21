<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_requires_authentication(): void
    {
        $this->get('/history')->assertRedirect(route('login'));
    }

    public function test_history_shows_only_current_users_completed_games_newest_first(): void
    {
        $user = User::factory()->create(['login' => 'chrono']);
        $gameList = $user->gameLists()->create([
            'name' => 'Основной список', 'slug' => 'main', 'default_platform' => 'nintendo_switch',
        ]);
        $gameList->games()->create([
            'title' => 'Hades II', 'normalized_title' => 'hades ii', 'status' => 'completed',
            'platform' => 'nintendo_switch', 'started_at' => '2026-07-01', 'completed_at' => '2026-07-11',
        ]);
        $gameList->games()->create([
            'title' => 'Metroid Dread', 'normalized_title' => 'metroid dread', 'status' => 'completed',
            'platform' => 'nintendo_switch', 'started_at' => '2026-07-12', 'completed_at' => '2026-07-13',
        ]);
        $gameList->games()->create([
            'title' => 'Zelda', 'normalized_title' => 'zelda', 'status' => 'playing',
            'platform' => 'nintendo_switch', 'started_at' => '2026-07-12',
        ]);
        $gameList->games()->create([
            'title' => 'Undated Game', 'normalized_title' => 'undated game', 'status' => 'installed',
            'platform' => 'nintendo_switch',
        ]);

        $other = User::factory()->create();
        $otherList = $other->gameLists()->create([
            'name' => 'Чужой список', 'slug' => 'other', 'default_platform' => 'pc',
        ]);
        $otherList->games()->create([
            'title' => 'Other Game', 'normalized_title' => 'other game', 'status' => 'completed',
            'platform' => 'pc', 'started_at' => '2026-07-14', 'completed_at' => '2026-07-15',
        ]);

        $page = $this->actingAs($user)->get(route('history.index'));

        $page->assertOk()
            ->assertSeeInOrder(['Мои списки', 'Моя история', 'Настройки'])
            ->assertSeeInOrder(['Metroid Dread', 'Hades II'])
            ->assertSee('Прошёл игру')
            ->assertSee('(за 10 дней)')
            ->assertSee('Основной список')
            ->assertDontSee('Начал играть')
            ->assertDontSee('Zelda')
            ->assertDontSee('Undated Game')
            ->assertDontSee('Other Game')
            ->assertDontSee('Чужой список');

        $this->assertSame(2, substr_count($page->getContent(), 'data-history-event'));
    }

    public function test_profile_links_to_public_history_without_exposing_private_lists(): void
    {
        $user = User::factory()->create(['login' => 'chrono']);
        $publicList = $user->gameLists()->create([
            'name' => 'Public Games', 'slug' => 'public-games', 'default_platform' => 'pc', 'is_public' => true,
        ]);
        $privateList = $user->gameLists()->create([
            'name' => 'Private Games', 'slug' => 'private-games', 'default_platform' => 'pc', 'is_public' => false,
        ]);
        $publicGame = $publicList->games()->create([
            'title' => 'Public Completion', 'normalized_title' => 'public completion', 'status' => 'completed',
            'platform' => 'pc', 'completed_at' => '2026-07-13',
        ]);
        $privateGame = $privateList->games()->create([
            'title' => 'Private Completion', 'normalized_title' => 'private completion', 'status' => 'completed',
            'platform' => 'pc', 'completed_at' => '2026-07-14',
        ]);

        $this->assertSame('/history/@chrono', route('history.show', $user->login, false));

        $this->get(route('profiles.show', $user->login))
            ->assertOk()
            ->assertSee('href="'.route('history.show', $user->login).'"', false)
            ->assertSee('data-profile-history', false);

        $this->get(route('history.show', 'CHRONO'))
            ->assertOk()
            ->assertSeeText('История @chrono')
            ->assertSee('Public Completion')
            ->assertSee('href="'.route('games.view', $publicGame).'"', false)
            ->assertDontSee('Private Completion')
            ->assertDontSee('Private Games');

        $this->actingAs($user)->get(route('history.show', $user->login))
            ->assertOk()
            ->assertSee('Public Completion')
            ->assertSee('Private Completion')
            ->assertSee('href="'.route('games.edit', $privateGame).'"', false);
    }

    public function test_empty_history_has_helpful_state(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Started Games', 'slug' => 'started-games', 'default_platform' => 'pc',
        ]);
        $list->games()->create([
            'title' => 'Started Only', 'normalized_title' => 'started only', 'status' => 'playing',
            'platform' => 'pc', 'started_at' => '2026-07-12',
        ]);

        $this->actingAs($user)
            ->get(route('history.index'))
            ->assertOk()
            ->assertSee('История пока пуста')
            ->assertDontSee('Started Only');
    }

    public function test_completion_duration_uses_days_and_calendar_months(): void
    {
        $game = new Game([
            'started_at' => '2026-01-01',
            'completed_at' => '2026-01-11',
        ]);
        $this->assertSame('10 дней', $game->completionDuration());

        $game->completed_at = '2026-02-15';
        $this->assertSame('1 месяц 14 дней', $game->completionDuration());

        $game->completed_at = '2026-01-01';
        $this->assertSame('1 день', $game->completionDuration());

        $game->started_at = null;
        $this->assertNull($game->completionDuration());
    }
}
