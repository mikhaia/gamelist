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

    public function test_history_shows_only_current_users_dated_games_newest_first(): void
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
            'title' => 'Other Game', 'normalized_title' => 'other game', 'status' => 'playing',
            'platform' => 'pc', 'started_at' => '2026-07-15',
        ]);

        $page = $this->actingAs($user)->get(route('history.index'));

        $page->assertOk()
            ->assertSeeInOrder(['Мои списки', 'Моя история', 'Настройки'])
            ->assertSeeInOrder(['Zelda', 'Hades II'])
            ->assertSee('Начал играть')
            ->assertSee('Прошёл игру')
            ->assertSee('(за 10 дней)')
            ->assertSee('Основной список')
            ->assertDontSee('Undated Game')
            ->assertDontSee('Other Game')
            ->assertDontSee('Чужой список');

        $this->assertSame(3, substr_count($page->getContent(), 'data-history-event'));
    }

    public function test_empty_history_has_helpful_state(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('history.index'))
            ->assertOk()
            ->assertSee('История пока пуста');
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
