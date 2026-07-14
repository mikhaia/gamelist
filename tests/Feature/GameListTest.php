<?php

namespace Tests\Feature;

use App\Models\GameList;
use App\Models\User;
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
            ->assertOk()->assertSee('Playing Game')->assertDontSee('Completed Game');
        $this->get(route('public.lists.show', ['login' => 'chrono', 'slug' => 'games'] + $query))
            ->assertOk()->assertSee('Playing Game')->assertDontSee('Completed Game');

        $this->actingAs($user)->get(route('lists.show', ['gameList' => $list] + $query))
            ->assertSee('Скопировать список (1)')
            ->assertSee('- Playing Game')
            ->assertDontSee('- Completed Game');
    }

    public function test_list_cover_is_optimized_and_replaced(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Games', 'slug' => 'games', 'default_platform' => 'nintendo_switch',
            'cover_path' => 'list-covers/old.webp',
        ]);
        Storage::disk('public')->put('list-covers/old.webp', 'old');

        $this->actingAs($user)->put(route('lists.update', $list), [
            'name' => 'Games',
            'slug' => 'games',
            'default_platform' => 'nintendo_switch',
            'is_public' => '1',
            'cover' => UploadedFile::fake()->image('list.jpg', 1800, 1200),
        ])->assertRedirect(route('lists.show', $list));

        $list->refresh();
        Storage::disk('public')->assertMissing('list-covers/old.webp');
        Storage::disk('public')->assertExists($list->cover_path);
        $this->assertStringEndsWith('.webp', $list->cover_path);
    }
}
