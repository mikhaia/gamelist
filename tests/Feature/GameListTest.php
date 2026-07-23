<?php

namespace Tests\Feature;

use App\Models\GameList;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GameListTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_list_and_public_can_read_it(): void
    {
        $user = User::factory()->create(['login' => 'chrono']);

        $this->actingAs($user)->post(route('lists.store'), [
            'name' => 'Switch Backlog',
            'slug' => 'switch-backlog',
            'default_platform' => 'nintendo_switch',
            'available_statuses' => ['want_to_play', 'installed', 'playing', 'completed', 'dropped'],
            'is_public' => '1',
        ])->assertRedirect();

        $list = GameList::firstOrFail();
        $this->get('/chrono/switch-backlog')
            ->assertOk()
            ->assertSee('Switch Backlog');
        $this->assertTrue($list->is_public);
    }

    public function test_private_list_is_not_publicly_visible(): void
    {
        $user = User::factory()->create(['login' => 'chrono']);
        $user->gameLists()->create([
            'name' => 'Secret', 'slug' => 'secret', 'default_platform' => 'pc', 'is_public' => false,
        ]);

        $this->get('/chrono/secret')->assertNotFound();
    }

    public function test_list_indexes_are_sorted_by_latest_game_update(): void
    {
        $user = User::factory()->create(['login' => 'sorter']);
        $mostRecentlyChanged = $user->gameLists()->create([
            'name' => 'Most Recently Changed', 'slug' => 'most-recently-changed', 'default_platform' => 'pc', 'is_public' => true,
        ]);
        $changedEarlier = $user->gameLists()->create([
            'name' => 'Changed Earlier', 'slug' => 'changed-earlier', 'default_platform' => 'pc', 'is_public' => true,
        ]);
        $emptyList = $user->gameLists()->create([
            'name' => 'Empty List', 'slug' => 'empty-list', 'default_platform' => 'pc', 'is_public' => true,
        ]);

        $latestGame = $mostRecentlyChanged->games()->create([
            'title' => 'Latest Game', 'normalized_title' => 'latest game', 'status' => 'playing', 'platform' => 'pc',
        ]);
        $earlierGame = $changedEarlier->games()->create([
            'title' => 'Earlier Game', 'normalized_title' => 'earlier game', 'status' => 'playing', 'platform' => 'pc',
        ]);

        $latestGame->forceFill(['updated_at' => Carbon::parse('2026-03-01')])->saveQuietly();
        $earlierGame->forceFill(['updated_at' => Carbon::parse('2026-02-01')])->saveQuietly();
        $mostRecentlyChanged->forceFill(['updated_at' => Carbon::parse('2026-01-01')])->saveQuietly();
        $changedEarlier->forceFill(['updated_at' => Carbon::parse('2026-04-01')])->saveQuietly();
        $emptyList->forceFill(['updated_at' => Carbon::parse('2026-05-01')])->saveQuietly();

        $expectedOrder = ['Most Recently Changed', 'Changed Earlier', 'Empty List'];

        $this->actingAs($user)->get(route('lists.index'))
            ->assertOk()
            ->assertSeeInOrder($expectedOrder);

        $this->get(route('profiles.show', $user->login))
            ->assertOk()
            ->assertSeeText('Списки sorter')
            ->assertSeeInOrder($expectedOrder);
    }

    public function test_another_user_cannot_edit_list(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $list = $owner->gameLists()->create([
            'name' => 'Owner list', 'slug' => 'owner-list', 'default_platform' => 'pc',
        ]);

        $this->actingAs($other)->get(route('lists.edit', $list))->assertForbidden();
    }

    public function test_list_can_be_filtered_and_clipboard_text_uses_same_status_filter(): void
    {
        $user = User::factory()->create(['login' => 'chrono']);
        $list = $user->gameLists()->create([
            'name' => 'Games', 'slug' => 'games', 'default_platform' => 'nintendo_switch', 'is_public' => true,
        ]);
        $list->games()->create([
            'title' => 'Playing Game', 'normalized_title' => 'playing game', 'status' => 'playing', 'platform' => 'pc',
        ]);
        $list->games()->create([
            'title' => 'Completed Game', 'normalized_title' => 'completed game', 'status' => 'completed', 'platform' => 'pc',
        ]);

        $query = ['status' => ['playing']];
        $this->actingAs($user)->get(route('lists.show', ['gameList' => $list] + $query))
            ->assertOk()
            ->assertSee('Playing Game')
            ->assertDontSee('Completed Game')
            ->assertSee('data-list-heading', false)
            ->assertSee('data-list-settings', false)
            ->assertSee('data-list-add-game', false)
            ->assertSee('bg-white/85 text-slate-950', false)
            ->assertSee('href="'.$list->public_path.'" target="_blank" rel="noopener noreferrer"', false)
            ->assertSee('data-public-list-link', false);
        $this->get(route('public.lists.show', ['login' => 'chrono', 'slug' => 'games'] + $query))
            ->assertOk()->assertSee('Playing Game')->assertDontSee('Completed Game');

        $this->actingAs($user)->get(route('lists.show', ['gameList' => $list] + $query))
            ->assertSee('Скопировать список (1)')
            ->assertSee('- Playing Game')
            ->assertDontSee('- Completed Game');
    }

    public function test_private_and_public_lists_can_sort_by_latest_completion_date(): void
    {
        $user = User::factory()->create(['login' => 'chrono']);
        $list = $user->gameLists()->create([
            'name' => 'Games', 'slug' => 'games', 'default_platform' => 'pc', 'is_public' => true,
        ]);

        try {
            Carbon::setTestNow('2026-04-01');
            $list->games()->create([
                'title' => 'Incomplete Game', 'normalized_title' => 'incomplete game', 'status' => 'playing', 'platform' => 'pc',
            ]);
            Carbon::setTestNow('2026-03-01');
            $list->games()->create([
                'title' => 'Added Later', 'normalized_title' => 'added later', 'status' => 'completed', 'platform' => 'pc',
                'completed_at' => '2026-01-10',
            ]);
            Carbon::setTestNow('2026-01-01');
            $list->games()->create([
                'title' => 'Completed Later', 'normalized_title' => 'completed later', 'status' => 'completed', 'platform' => 'pc',
                'completed_at' => '2026-02-20',
            ]);
        } finally {
            Carbon::setTestNow();
        }

        $this->actingAs($user)->get(route('lists.show', $list))
            ->assertOk()
            ->assertSee('По дате добавления')
            ->assertSee('По дате прохождения')
            ->assertSeeInOrder(['Incomplete Game', 'Added Later', 'Completed Later']);

        $sortQuery = ['sort' => 'completed_at'];
        $this->actingAs($user)->get(route('lists.show', ['gameList' => $list] + $sortQuery))
            ->assertOk()
            ->assertSee('name="sort"', false)
            ->assertSee('value="completed_at" selected', false)
            ->assertSeeInOrder(['Completed Later', 'Added Later', 'Incomplete Game']);

        $this->get(route('public.lists.show', ['login' => 'chrono', 'slug' => 'games'] + $sortQuery))
            ->assertOk()
            ->assertSeeInOrder(['Completed Later', 'Added Later', 'Incomplete Game']);

        $filteredQuery = ['status' => ['completed'], 'sort' => 'completed_at'];
        $this->actingAs($user)->get(route('lists.show', ['gameList' => $list] + $filteredQuery))
            ->assertOk()
            ->assertSee('name="status[]" value="completed"', false)
            ->assertSee('sort=completed_at', false)
            ->assertDontSee('Incomplete Game');
    }

    public function test_owner_can_delete_list_from_edit_page(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['login' => 'list_owner']);
        $other = User::factory()->create(['login' => 'other_player']);
        $list = $owner->gameLists()->create([
            'name' => 'Disposable',
            'slug' => 'disposable',
            'default_platform' => 'pc',
            'cover_path' => 'list-covers/disposable.webp',
        ]);
        $game = $list->games()->create([
            'title' => 'Temporary Game',
            'normalized_title' => 'temporary game',
            'status' => 'playing',
            'platform' => 'pc',
            'cover_path' => 'game-covers/temporary.webp',
        ]);
        Storage::disk('public')->put($list->cover_path, 'list cover');
        Storage::disk('public')->put($game->cover_path, 'game cover');

        $this->actingAs($owner)->get(route('lists.edit', $list))
            ->assertOk()
            ->assertSee('form="delete-list"', false)
            ->assertSee('action="'.route('lists.destroy', $list).'"', false)
            ->assertSee('Удалить список')
            ->assertSee('data-confirm=', false)
            ->assertSee('data-confirm-title="Удалить список?"', false)
            ->assertSee('data-confirm-label="Удалить список"', false)
            ->assertSee('data-confirm-dialog', false)
            ->assertSee('bg-white/90', false);

        $this->actingAs($other)->delete(route('lists.destroy', $list))->assertForbidden();
        $this->assertDatabaseHas('game_lists', ['id' => $list->id]);

        $this->actingAs($owner)->delete(route('lists.destroy', $list))
            ->assertRedirect(route('lists.index'))
            ->assertSessionHas('success', __('app.messages.list_deleted'));

        $this->assertDatabaseMissing('game_lists', ['id' => $list->id]);
        $this->assertDatabaseMissing('games', ['id' => $game->id]);
        Storage::disk('public')->assertMissing($list->cover_path);
        Storage::disk('public')->assertMissing($game->cover_path);
    }

    public function test_list_cover_is_optimized_and_replaced(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['login' => 'cover_player']);
        $list = $user->gameLists()->create([
            'name' => 'Games', 'slug' => 'games', 'default_platform' => 'nintendo_switch',
            'cover_path' => 'list-covers/old.webp',
        ]);
        Storage::disk('public')->put('list-covers/old.webp', 'old');

        $this->actingAs($user)->put(route('lists.update', $list), [
            'name' => 'Games',
            'slug' => 'games',
            'default_platform' => 'nintendo_switch',
            'available_statuses' => ['want_to_play', 'installed', 'playing', 'completed', 'dropped'],
            'is_public' => '1',
            'cover' => UploadedFile::fake()->image('list.jpg', 1800, 1200),
        ])->assertRedirect(route('lists.show', $list));

        $list->refresh();
        Storage::disk('public')->assertMissing('list-covers/old.webp');
        Storage::disk('public')->assertExists($list->cover_path);
        $this->assertStringEndsWith('.webp', $list->cover_path);

        $this->get(route('public.lists.show', [$user->login, $list->slug]))
            ->assertOk()
            ->assertSee('p-5 text-left', false)
            ->assertSee('mb-3 flex flex-wrap items-center gap-2', false)
            ->assertDontSee('mb-3 flex flex-wrap items-center justify-center gap-2', false)
            ->assertSee('bg-gradient-to-r from-[#080a14]/90 via-[#080a14]/55 to-[#080a14]/15', false);
    }
}
