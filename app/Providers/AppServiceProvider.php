<?php

namespace App\Providers;

use App\Contracts\GameCatalog;
use App\Models\Game;
use App\Models\GameList;
use App\Observers\GameListObserver;
use App\Observers\GameObserver;
use App\Services\HowLongToBeatCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GameCatalog::class, HowLongToBeatCatalog::class);
        $this->app->singleton(ImageManagerInterface::class, fn (): ImageManager => new ImageManager(
            driver: config('intervention-image.driver'),
            autoOrientation: config('intervention-image.options.autoOrientation', true),
            decodeAnimation: config('intervention-image.options.decodeAnimation', false),
            backgroundColor: config('intervention-image.options.backgroundColor', 'ffffff'),
            strip: config('intervention-image.options.strip', true),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Game::observe(GameObserver::class);
        GameList::observe(GameListObserver::class);

        View::composer('layouts.app', function ($view): void {
            $user = Auth::user();
            $view->with([
                'navigationNotifications' => $user?->notifications()->latest()->limit(50)->get() ?? collect(),
                'navigationNotificationCount' => $user?->notifications()->count() ?? 0,
            ]);
        });
    }
}
