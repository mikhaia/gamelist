<?php

namespace App\Http\Controllers;

use App\Models\CatalogGame;
use App\Models\GameList;
use App\Services\CatalogGameListAdder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogQuickAddController extends Controller
{
    public function __construct(private readonly CatalogGameListAdder $games) {}

    public function __invoke(Request $request, GameList $gameList, CatalogGame $catalogGame): JsonResponse
    {
        abort_unless($gameList->user_id === $request->user()->id, 403);

        $game = $this->games->add($gameList, $catalogGame);
        if (! $game) {
            return response()->json(['message' => 'Игра уже есть в этом списке.'], 409);
        }

        return response()->json([
            'message' => 'Игра добавлена.',
            'game_id' => $game->id,
        ], 201);
    }
}
