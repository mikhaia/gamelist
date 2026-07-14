<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameImportController;
use App\Http\Controllers\GameListController;
use App\Http\Controllers\GameListExportController;
use App\Http\Controllers\PublicListController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::resource('lists', GameListController::class)->parameters(['lists' => 'gameList']);
    Route::patch('/lists/{gameList}/display', [GameListController::class, 'display'])->name('lists.display');
    Route::get('/lists/{gameList}/export', [GameListExportController::class, 'owner'])->name('lists.export');

    Route::get('/lists/{gameList}/games/create', [GameController::class, 'create'])->name('games.create');
    Route::post('/lists/{gameList}/games', [GameController::class, 'store'])->name('games.store');
    Route::get('/games/{game}/edit', [GameController::class, 'edit'])->name('games.edit');
    Route::put('/games/{game}', [GameController::class, 'update'])->name('games.update');
    Route::patch('/games/{game}/status', [GameController::class, 'status'])->name('games.status');
    Route::delete('/games/{game}', [GameController::class, 'destroy'])->name('games.destroy');

    Route::get('/lists/{gameList}/import', [GameImportController::class, 'create'])->name('imports.create');
    Route::post('/lists/{gameList}/import/preview', [GameImportController::class, 'preview'])->name('imports.preview');
    Route::post('/lists/{gameList}/import', [GameImportController::class, 'store'])->name('imports.store');

    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::patch('/settings/avatar', [SettingsController::class, 'avatar'])->name('settings.avatar');
    Route::patch('/settings/password', [SettingsController::class, 'password'])->name('settings.password');
});

Route::get('/{login}/{slug}/export', [GameListExportController::class, 'public'])
    ->where(['login' => '[A-Za-z0-9_]+', 'slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->name('public.lists.export');

Route::get('/{login}/{slug}', [PublicListController::class, 'show'])
    ->where(['login' => '[A-Za-z0-9_]+', 'slug' => '[a-z0-9]+(?:-[a-z0-9]+)*'])
    ->name('public.lists.show');
