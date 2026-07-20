<?php

namespace Tests\Feature;

use App\Enums\Achievement;
use App\Models\CatalogGame;
use App\Models\Friendship;
use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    public function test_achievement_catalog_has_short_titles_and_juicy_descriptions(): void
    {
        $this->assertCount(36, Achievement::cases());

        foreach (Achievement::cases() as $achievement) {
            $this->assertLessThanOrEqual(35, mb_strlen($achievement->title()), $achievement->value);
            $this->assertNotSame($achievement->title(), $achievement->description(), $achievement->value);
            $this->assertNotSame($achievement->description(), $achievement->requirement(), $achievement->value);
        }
    }

    public function test_game_achievements_are_awarded_once_and_notify_the_owner_and_followers(): void
    {
        $owner = User::factory()->create(['login' => 'owner']);
        $follower = User::factory()->create();
        Friendship::query()->create(['user_id' => $follower->id, 'friend_id' => $owner->id]);
        $list = $owner->gameLists()->create([
            'name' => 'Games', 'slug' => 'games', 'default_platform' => 'pc',
        ]);

        foreach (range(1, 3) as $number) {
            $list->games()->create([
                'title' => "Dropped {$number}",
                'normalized_title' => "dropped {$number}",
                'status' => 'dropped',
                'platform' => 'pc',
            ]);
            $list->games()->create([
                'title' => "Completed {$number}",
                'normalized_title' => "completed {$number}",
                'status' => 'completed',
                'platform' => 'pc',
            ]);
        }

        $this->assertEqualsCanonicalizing([
            Achievement::Games1,
            Achievement::Drops1,
            Achievement::Drops3,
            Achievement::Completions1,
            Achievement::Completions3,
        ], $owner->achievements()->pluck('key')->all());
        $this->assertSame(5, $owner->notifications()->where('data->event', 'achievement_unlocked')->count());
        $this->assertSame(5, $follower->notifications()->where('data->event', 'friend_achievement_unlocked')->count());

        $this->assertCount(0, app(AchievementService::class)->evaluate($owner));
        $this->assertSame(5, $owner->achievements()->count());
    }

    public function test_opinion_and_rating_achievements_are_evaluated_independently(): void
    {
        $user = User::factory()->create();
        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 101,
            'title' => 'Control',
            'normalized_title' => 'control',
        ]);

        $this->actingAs($user)->put(route('game-reviews.update', $catalogGame), [
            'rating' => 8,
        ])->assertRedirect(route('games.show', $catalogGame));
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'key' => Achievement::Ratings1->value,
        ]);
        $this->assertDatabaseMissing('user_achievements', [
            'user_id' => $user->id,
            'key' => Achievement::Opinions1->value,
        ]);

        $this->actingAs($user)->put(route('game-reviews.update', $catalogGame), [
            'rating' => 8,
            'body' => 'Сильная атмосфера и отличный мир.',
        ])->assertRedirect(route('games.show', $catalogGame));
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id,
            'key' => Achievement::Opinions1->value,
        ]);
    }

    public function test_achievement_page_places_unlocked_items_before_locked_ones(): void
    {
        $user = User::factory()->create(['login' => 'chrono', 'email' => 'chrono@example.com']);
        app(AchievementService::class)->evaluate($user);

        $this->get(route('achievements.show', $user))
            ->assertOk()
            ->assertSee('1 из 36 достижений разблокировано.')
            ->assertSeeInOrder(['Получены', Achievement::GoldStatus->title(), 'Ещё впереди'])
            ->assertSee('grayscale', false);
    }
}
