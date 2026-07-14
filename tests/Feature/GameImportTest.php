<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_preview_strips_markdown_and_marks_duplicates(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);
        $list->games()->create([
            'title' => 'Hades II', 'normalized_title' => 'hades ii', 'status' => 'playing', 'platform' => 'nintendo_switch',
        ]);

        $this->actingAs($user)->post(route('imports.preview', $list), [
            'games_text' => "- [ ] Metroid Prime 4\n1. Hades II\n- [x] Metroid Prime 4",
        ])->assertOk()
            ->assertSee('Metroid Prime 4')
            ->assertSee('Уже в списке')
            ->assertSee('Повтор строки');
    }

    public function test_selected_games_are_imported_without_duplicates(): void
    {
        $user = User::factory()->create();
        $list = $user->gameLists()->create([
            'name' => 'Switch', 'slug' => 'switch', 'default_platform' => 'nintendo_switch',
        ]);

        $this->actingAs($user)->post(route('imports.store', $list), [
            'titles' => ['Metroid Prime 4', 'metroid prime 4', 'Hades II'],
            'status' => 'want_to_play',
            'platform' => 'nintendo_switch',
        ])->assertRedirect(route('lists.show', $list));

        $this->assertDatabaseCount('games', 2);
    }
}
