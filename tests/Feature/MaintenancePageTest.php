<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MaintenancePageTest extends TestCase
{
    public function test_custom_maintenance_page_is_friendly_and_contains_prepared_games(): void
    {
        Route::get('/maintenance-preview', fn () => response()->view('errors.503', status: 503));

        $this->get('/maintenance-preview')
            ->assertServiceUnavailable()
            ->assertSee('Мы деплоимся')
            ->assertSee('Проверить ещё раз')
            ->assertSee('Persona 5 Royal')
            ->assertSee('Baldur’s Gate 3')
            ->assertSee('Elden Ring')
            ->assertSee('The Witcher 3')
            ->assertDontSee('503 Service Unavailable');
    }
}
