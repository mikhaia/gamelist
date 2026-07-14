<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
}
