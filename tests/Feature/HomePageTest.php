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
            ->assertSee('Featured Game 1')
            ->assertSee('Featured Game 2')
            ->assertSee('Featured Game 3')
            ->assertSee('https://images.example.com/game-1.jpg', false)
            ->assertDontSee('Game Without Cover');

        $this->assertSame(3, substr_count($page->getContent(), 'data-featured-game'));
    }

    public function test_home_page_keeps_placeholders_when_catalog_has_no_covers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Новая игра', false);
    }
}
