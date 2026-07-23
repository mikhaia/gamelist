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

        CatalogGame::create([
            'hltb_id' => 3001,
            'title' => 'Game With Unreliable Steam Cover',
            'normalized_title' => 'game with unreliable steam cover',
            'cover_url' => 'https://shared.fastly.steamstatic.com/store_item_assets/steam/apps/620/library_600x900.jpg',
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
            ->assertDontSee('Game Without Cover')
            ->assertDontSee('Game With Unreliable Steam Cover')
            ->assertDontSee('shared.fastly.steamstatic.com', false);

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

    public function test_yandex_metrika_counter_is_loaded_once(): void
    {
        $page = $this->get(route('home'));

        $page->assertOk()
            ->assertSee('https://mc.yandex.ru/metrika/tag.js?id=110936413', false)
            ->assertSee("ym(110936413, 'init'", false)
            ->assertSee('https://mc.yandex.ru/watch/110936413', false);

        $this->assertSame(1, substr_count($page->getContent(), "ym(110936413, 'init'"));
    }

    public function test_google_analytics_tag_is_loaded_once(): void
    {
        $page = $this->get(route('home'));

        $page->assertOk()
            ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-1SYC1T2FGV', false)
            ->assertSee("gtag('config', 'G-1SYC1T2FGV')", false);

        $this->assertSame(1, substr_count($page->getContent(), "gtag('config', 'G-1SYC1T2FGV')"));
    }
}
