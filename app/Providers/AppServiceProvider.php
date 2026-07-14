<?php

namespace App\Providers;

use App\Contracts\GameCatalog;
use App\Services\HowLongToBeatCatalog;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GameCatalog::class, HowLongToBeatCatalog::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
