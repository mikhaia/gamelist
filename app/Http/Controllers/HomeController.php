<?php

namespace App\Http\Controllers;

use App\Models\CatalogGame;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $featuredGames = CatalogGame::query()
            ->whereNotNull('cover_url')
            ->where('cover_url', '!=', '')
            ->inRandomOrder()
            ->limit(3)
            ->get();

        return view('welcome', compact('featuredGames'));
    }
}
