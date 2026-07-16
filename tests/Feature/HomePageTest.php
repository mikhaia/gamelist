<?php

namespace Tests\Feature;

use App\Models\CatalogGame;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_uses_random_cached_games_with_covers(): void
    {
        foreach (range(1, 3) as $number) {
            CatalogGame::create([
                'hltb_id' => 2000 + $number,
                'title' => 'Featured Game '.$number,
                'normalized_title' => 'featured game '.$number,
                'cover_url' => 'https://images.example.com/game-'.$number.'.jpg',
            ]);
        }

        CatalogGame::create([
            'hltb_id' => 3000,
            'title' => 'Game Without Cover',
            'normalized_title' => 'game without cover',
        ]);

        $page = $this->get(route('home'));

        $page->assertOk()
            ->assertSee('@zerocool')
            ->assertSee('Steam')
            ->assertDontSee('Случайная подборка')
            ->assertDontSee('Из каталога')
            ->assertSee('Featured Game 1')
            ->assertSee('Featured Game 2')
            ->assertSee('Featured Game 3')
            ->assertSee('https://images.example.com/game-1.jpg', false)
            ->assertSee('href="'.route('games.show', 1).'"', false)
            ->assertSee('href="'.route('games.show', 2).'"', false)
            ->assertSee('href="'.route('games.show', 3).'"', false)
            ->assertSee('aria-label="Открыть страницу игры Featured Game 1"', false)
            ->assertSeeInOrder(['Хочу сыграть', 'Играю', 'Пройдена'])
            ->assertDontSee('Game Without Cover');

        $this->assertSame(3, substr_count($page->getContent(), 'data-featured-game'));
    }

    public function test_home_page_keeps_placeholders_when_catalog_has_no_covers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Новая игра', false)
            ->assertSeeInOrder(['Хочу сыграть', 'Играю', 'Пройдена'])
            ->assertDontSee('data-featured-game', false);
    }

    public function test_material_symbols_font_is_loaded_locally(): void
    {
        $this->assertFileExists(public_path('fonts/material-symbols-outlined.woff2'));

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('fonts/material-symbols-outlined.woff2', false)
            ->assertDontSee('family=Material+Symbols+Outlined', false);
    }
}
