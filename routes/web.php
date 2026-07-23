<?php

use App\Http\Controllers\AchievementController;
use App\Http\Controllers\Admin\CatalogGameController as AdminCatalogGameController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ErrorLogController as AdminErrorLogController;
use App\Http\Controllers\Admin\FileController as AdminFileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\OtpPasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SteamAuthController;
use App\Http\Controllers\CatalogBrowserController;
use App\Http\Controllers\CatalogQuickAddController;
use App\Http\Controllers\CatalogSearchController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\GameCommentController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameDetailController;
use App\Http\Controllers\GameImportController;
use App\Http\Controllers\GameLibraryController;
use App\Http\Controllers\GameListController;
use App\Http\Controllers\GamePageController;
use App\Http\Controllers\GamePersonalDetailsController;
use App\Http\Controllers\GameReviewController;
use App\Http\Controllers\GameScreenshotController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileFavoriteController;
use App\Http\Controllers\PublicListController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\RawgCatalogSearchController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SteamLibraryController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/game/{catalogGame}', GamePageController::class)
    ->whereNumber('catalogGame')
    ->name('games.show');
Route::get('/search', [CatalogBrowserController::class, 'search'])->name('search.index');
Route::get('/search/results', [CatalogBrowserController::class, 'searchResults'])->middleware('throttle:120,1')->name('search.results');
Route::get('/catalog/search/cached', [CatalogSearchController::class, 'cached'])->middleware('throttle:120,1')->name('catalog.cached');
Route::get('/catalog/search', [CatalogSearchController::class, 'fresh'])->middleware('throttle:30,1')->name('catalog.search');
Route::get('/catalog/search/rawg', RawgCatalogSearchController::class)->middleware('throttle:30,1')->name('catalog.rawg-search');
Route::get('/achievements/{user:login}', [AchievementController::class, 'show'])->name('achievements.show');
Route::get('/auth/steam', [SteamAuthController::class, 'redirect'])->middleware('throttle:20,1')->name('steam.redirect');
Route::get('/auth/steam/callback', [SteamAuthController::class, 'callback'])->middleware('throttle:20,1')->name('steam.callback');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', [OtpPasswordResetController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [OtpPasswordResetController::class, 'send'])->middleware('throttle:10,1')->name('password.email');
    Route::get('/reset-password', [OtpPasswordResetController::class, 'edit'])->name('password.otp');
    Route::post('/reset-password', [OtpPasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/achievements', [AchievementController::class, 'index'])->name('achievements.index');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/history', [HistoryController::class, 'index'])->name('history.index');
    Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
    Route::post('/friends/{friend}', [FriendController::class, 'store'])->name('friends.store');
    Route::delete('/friends/{friend}', [FriendController::class, 'destroy'])->name('friends.destroy');
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications', [NotificationController::class, 'clear'])->name('notifications.clear');
    Route::get('/lists/steam/import', [SteamLibraryController::class, 'create'])->name('lists.steam.create');
    Route::post('/lists/steam/import', [SteamLibraryController::class, 'store'])->middleware('throttle:3,1')->name('lists.steam.import');
    Route::resource('lists', GameListController::class)->parameters(['lists' => 'gameList']);
    Route::patch('/lists/{gameList}/display', [GameListController::class, 'display'])->name('lists.display');
    Route::get('/lists/{gameList}/catalog', [CatalogBrowserController::class, 'index'])->name('catalog.index');
    Route::get('/lists/{gameList}/catalog/results', [CatalogBrowserController::class, 'results'])->middleware('throttle:120,1')->name('catalog.results');
    Route::post('/lists/{gameList}/catalog/{catalogGame}', CatalogQuickAddController::class)->middleware('throttle:60,1')->name('catalog.add');

    Route::get('/lists/{gameList}/games/create', [GameController::class, 'create'])->name('games.create');
    Route::post('/lists/{gameList}/games', [GameController::class, 'store'])->name('games.store');
    Route::get('/games/{game}/edit', [GameController::class, 'edit'])->name('games.edit');
    Route::put('/games/{game}', [GameController::class, 'update'])->name('games.update');
    Route::patch('/games/{game}/status', [GameController::class, 'status'])->name('games.status');
    Route::patch('/games/{game}/personal-details', [GamePersonalDetailsController::class, 'update'])->name('games.personal-details.update');
    Route::delete('/games/{game}', [GameController::class, 'destroy'])->name('games.destroy');
    Route::post('/games/{game}/screenshots', [GameScreenshotController::class, 'store'])->name('games.screenshots.store');
    Route::delete('/games/{game}/screenshots/{screenshot}', [GameScreenshotController::class, 'destroy'])->name('games.screenshots.destroy');
    Route::post('/games/{game}/comments', [GameCommentController::class, 'store'])->name('games.comments.store');
    Route::patch('/games/{game}/comments/{comment}/visibility', [GameCommentController::class, 'toggleVisibility'])->name('games.comments.visibility');
    Route::post('/game/review/preview', [GameReviewController::class, 'preview'])->middleware('throttle:60,1')->name('game-reviews.preview');
    Route::post('/game/{catalogGame}/add', [GameLibraryController::class, 'store'])->whereNumber('catalogGame')->name('game-library.store');
    Route::put('/game/{catalogGame}/review', [GameReviewController::class, 'update'])->whereNumber('catalogGame')->name('game-reviews.update');
    Route::patch('/game/{catalogGame}/rating', [GameReviewController::class, 'updateRating'])->whereNumber('catalogGame')->name('game-reviews.rating.update');
    Route::patch('/game/{catalogGame}/opinion', [GameReviewController::class, 'updateOpinion'])->whereNumber('catalogGame')->name('game-reviews.opinion.update');
    Route::delete('/game/{catalogGame}/review', [GameReviewController::class, 'destroy'])->whereNumber('catalogGame')->name('game-reviews.destroy');

    Route::get('/lists/{gameList}/import', [GameImportController::class, 'create'])->name('imports.create');
    Route::post('/lists/{gameList}/import/preview', [GameImportController::class, 'preview'])->name('imports.preview');
    Route::post('/lists/{gameList}/import', [GameImportController::class, 'store'])->name('imports.store');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::get('/settings/avatar', [SettingsController::class, 'avatarEdit'])->name('settings.avatar.edit');
    Route::patch('/settings/avatar', [SettingsController::class, 'avatar'])->name('settings.avatar');
    Route::patch('/settings/email', [SettingsController::class, 'email'])->name('settings.email');
    Route::patch('/settings/profile-cover', [SettingsController::class, 'profileCover'])->name('settings.profile-cover');
    Route::patch('/settings/password', [SettingsController::class, 'password'])->name('settings.password');
    Route::delete('/settings/steam', [SteamAuthController::class, 'destroy'])->name('settings.steam.destroy');
    Route::patch('/profile/favorites', [ProfileFavoriteController::class, 'update'])->name('profile.favorites.update');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', EnsureUserIsAdmin::class])
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('/users', AdminUserController::class)->name('users.index');
        Route::get('/games', AdminCatalogGameController::class)->name('games.index');
        Route::get('/errors', AdminErrorLogController::class)->name('errors.index');
        Route::get('/files/{type?}', [AdminFileController::class, 'index'])
            ->where('type', 'screenshots|avatars|list-covers|game-covers')
            ->name('files.index');
        Route::get('/files/{type}/{id}/download', [AdminFileController::class, 'download'])
            ->where('type', 'screenshots|avatars|list-covers|game-covers')
            ->whereNumber('id')
            ->name('files.download');
    });

Route::get('/history/{login}', [HistoryController::class, 'show'])
    ->where('login', '[A-Za-z0-9_]+')
    ->name('history.show');

Route::get('/games/{game}', [GameDetailController::class, 'show'])->name('games.view');

Route::get('/{login}', PublicProfileController::class)
    ->where('login', '[A-Za-z0-9_]+')
    ->name('profiles.show');

Route::get('/{login}/{slug}', [PublicListController::class, 'show'])
    ->where(['login' => '[A-Za-z0-9_]+', 'slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->name('public.lists.show');
