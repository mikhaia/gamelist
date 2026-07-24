<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    public function test_custom_not_found_page_offers_safe_navigation(): void
    {
        $this->get('/this-page-definitely-does-not-exist')
            ->assertStatus(404)
            ->assertSee('Эта страница вышла из игры')
            ->assertSee('На главную')
            ->assertSee('Найти игру')
            ->assertSee('Ищем новый маршрут')
            ->assertSee('prefers-reduced-motion', false)
            ->assertDontSee('Stack trace')
            ->assertDontSee('Symfony');
    }

    public function test_custom_server_error_page_is_friendly_and_does_not_expose_details(): void
    {
        Route::get('/server-error-preview', fn () => response()->view('errors.500', status: 500));

        $this->get('/server-error-preview')
            ->assertStatus(500)
            ->assertSee('Сервер пропустил ход')
            ->assertSee('Попробовать снова')
            ->assertSee('На главную')
            ->assertSee('Переподключаемся')
            ->assertSee('prefers-reduced-motion', false)
            ->assertDontSee('Stack trace')
            ->assertDontSee('Internal Server Error');
    }
}
