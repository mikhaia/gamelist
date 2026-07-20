<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameTest extends TestCase
{
    use RefreshDatabase;

    public function test_game_can_be_added_manually_with_local_optimized_cover(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);

        $this->actingAs($user)->post(route('games.store', $list), [
            'title' => 'Metroid Prime 4',
            'status' => 'want_to_play',
            'platform' => 'nintendo_switch',
            'cover' => UploadedFile::fake()->image('cover.jpg', 1200, 1600),
        ])->assertRedirect(route('lists.show', $list));

        $game = $list->games()->firstOrFail();
        $this->assertStringEndsWith('.webp', $game->cover_path);
        Storage::disk('public')->assertExists($game->cover_path);
    }

    public function test_game_dates_are_saved_and_replacement_deletes_old_cover(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);
        $game = $list->games()->create([
            'title' => 'Hades II', 'normalized_title' => 'hades ii', 'status' => 'playing',
            'platform' => 'nintendo_switch', 'cover_path' => 'game-covers/old.webp',
        ]);
        Storage::disk('public')->put('game-covers/old.webp', 'old');

        $this->actingAs($user)->put(route('games.update', $game), [
            'title' => 'Hades II',
            'status' => 'completed',
            'platform' => 'nintendo_switch',
            'started_at' => '2026-07-01',
            'completed_at' => '2026-07-14',
            'cover' => UploadedFile::fake()->image('new-cover.jpg', 900, 1200),
        ])->assertRedirect(route('lists.show', $list));

        $game->refresh();
        $this->assertSame('2026-07-01', $game->started_at->format('Y-m-d'));
        $this->assertSame('2026-07-14', $game->completed_at->format('Y-m-d'));
        Storage::disk('public')->assertMissing('game-covers/old.webp');
        Storage::disk('public')->assertExists($game->cover_path);
    }

    public function test_game_can_be_moved_to_another_of_the_users_lists(): void
    {
        $user = User::factory()->create();
        $sourceList = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);
        $targetList = $user->gameLists()->create([
            'name' => 'PC', 'slug' => 'pc', 'default_platform' => 'pc',
        ]);
        $game = $sourceList->games()->create([
            'title' => 'Hades II', 'normalized_title' => 'hades ii', 'status' => 'want_to_play',
            'platform' => 'nintendo_switch',
        ]);

        $this->actingAs($user)->put(route('games.update', $game), [
            'title' => 'Hades II',
            'status' => 'want_to_play',
            'platform' => 'nintendo_switch',
            'game_list_id' => $targetList->id,
        ])->assertRedirect(route('lists.show', $targetList));

        $this->assertSame($targetList->id, $game->fresh()->game_list_id);
    }

    public function test_status_changes_fill_missing_started_and_completed_dates(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Games', 'slug' => 'games', 'default_platform' => 'pc',
        ]);
        $game = $list->games()->create([
            'title' => 'Control',
            'normalized_title' => 'control',
            'status' => 'want_to_play',
            'platform' => 'pc',
        ]);

        $this->travelTo(Carbon::parse('2026-07-15 12:00:00'));
        $this->actingAs($user)
            ->patch(route('games.status', $game), ['status' => 'playing'])
            ->assertRedirect();

        $game->refresh();
        $this->assertSame('2026-07-15', $game->started_at->format('Y-m-d'));
        $this->assertNull($game->completed_at);

        $this->travelTo(Carbon::parse('2026-07-20 12:00:00'));
        $this->actingAs($user)
            ->patchJson(route('games.status', $game), ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('status', 'completed')
            ->assertJsonPath('label', 'Пройдена')
            ->assertJsonPath('icon', 'trophy')
            ->assertJsonPath('started_at', '2026-07-15')
            ->assertJsonPath('completed_at', '2026-07-20');

        $game->refresh();
        $this->assertSame('2026-07-15', $game->started_at->format('Y-m-d'));
        $this->assertSame('2026-07-20', $game->completed_at->format('Y-m-d'));
    }
}
