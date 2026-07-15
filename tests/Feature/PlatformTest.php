<?php

namespace Tests\Feature;

use App\Models\GameList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_playstation_and_xbox_are_available_in_forms_and_accepted(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('lists.create'))
            ->assertOk()
            ->assertSee('PlayStation')
            ->assertSee('Xbox');

        $this->actingAs($user)
            ->post(route('lists.store'), [
                'name' => 'Консоли',
                'slug' => 'consoles',
                'default_platform' => 'playstation',
                'available_statuses' => ['want_to_play', 'installed', 'playing', 'completed', 'dropped'],
            ])
            ->assertRedirect();

        $gameList = GameList::query()->where('slug', 'consoles')->firstOrFail();

        $this->actingAs($user)
            ->post(route('games.store', $gameList), [
                'title' => 'Forza Horizon 5',
                'status' => 'want_to_play',
                'platform' => 'xbox',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('game_lists', [
            'id' => $gameList->id,
            'default_platform' => 'playstation',
        ]);
        $this->assertDatabaseHas('games', [
            'game_list_id' => $gameList->id,
            'title' => 'Forza Horizon 5',
            'platform' => 'xbox',
        ]);
    }
}
