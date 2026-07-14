<?php

namespace Tests\Feature;

use App\Models\GameList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
