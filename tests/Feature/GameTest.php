<?php

namespace Tests\Feature;

use App\Models\CatalogGame;
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

    public function test_duplicate_check_uses_title_or_non_null_catalog_game_id_across_lists_and_can_be_overridden(): void
    {
        $user = User::factory()->create();
        $firstList = $user->gameLists()->create([
            'name' => 'First', 'slug' => 'first', 'default_platform' => 'pc',
        ]);
        $secondList = $user->gameLists()->create([
            'name' => 'Second', 'slug' => 'second', 'default_platform' => 'pc',
        ]);
        $catalogGame = CatalogGame::query()->create([
            'hltb_id' => 4242,
            'title' => 'Catalog Original',
            'normalized_title' => 'catalog original',
        ]);
        $existing = $firstList->games()->create([
            'catalog_game_id' => $catalogGame->id,
            'title' => 'Existing Title',
            'normalized_title' => 'existing title',
            'status' => 'playing',
            'platform' => 'pc',
        ]);
        $firstList->games()->create([
            'title' => 'Manual Existing',
            'normalized_title' => 'manual existing',
            'status' => 'playing',
            'platform' => 'pc',
        ]);

        $duplicateByTitle = $this->from(route('games.create', $secondList))
            ->actingAs($user)
            ->post(route('games.store', $secondList), [
                'title' => 'Existing Title',
                'status' => 'want_to_play',
                'platform' => 'pc',
            ]);
        $duplicateByTitle->assertRedirect(route('games.create', $secondList))
            ->assertSessionHas('duplicateGame', fn (array $duplicate): bool => $duplicate['id'] === $existing->id);
        $this->assertDatabaseCount('games', 2);

        $this->get(route('games.create', $secondList))
            ->assertOk()
            ->assertSee('Такая игра уже добавлена')
            ->assertSee('href="'.route('games.edit', $existing).'"', false)
            ->assertSee('Остаться и изменить данные')
            ->assertSee('form="game-form" name="allow_duplicate" value="1"', false)
            ->assertSee('value="Existing Title"', false);

        $this->actingAs($user)->post(route('games.store', $secondList), [
            'title' => 'Different Catalog Title',
            'hltb_id' => $catalogGame->hltb_id,
            'status' => 'want_to_play',
            'platform' => 'pc',
        ])->assertSessionHas('duplicateGame', fn (array $duplicate): bool => $duplicate['id'] === $existing->id);
        $this->assertDatabaseCount('games', 2);

        $this->actingAs($user)->post(route('games.store', $secondList), [
            'title' => 'Different Manual Title',
            'status' => 'want_to_play',
            'platform' => 'pc',
        ])->assertRedirect(route('lists.show', $secondList));
        $this->assertDatabaseHas('games', [
            'game_list_id' => $secondList->id,
            'title' => 'Different Manual Title',
            'catalog_game_id' => null,
        ]);

        $this->actingAs($user)->post(route('games.store', $firstList), [
            'title' => 'Existing Title',
            'status' => 'want_to_play',
            'platform' => 'pc',
            'allow_duplicate' => '1',
        ])->assertRedirect(route('lists.show', $firstList));
        $this->assertSame(2, $firstList->games()->where('title', 'Existing Title')->count());
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
