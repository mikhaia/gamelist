<?php

namespace App\Http\Controllers;

use App\Models\CatalogGame;
use App\Models\GameList;
use App\Services\CatalogGameListAdder;
use App\Services\GameDuplicateDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogQuickAddController extends Controller
{
    public function __construct(
        private readonly CatalogGameListAdder $games,
        private readonly GameDuplicateDetector $duplicates,
    ) {}

    public function __invoke(Request $request, GameList $gameList, CatalogGame $catalogGame): JsonResponse
    {
        abort_unless($gameList->user_id === $request->user()->id, 403);

        $duplicate = $this->games->duplicate($gameList, $catalogGame);
        if ($duplicate && ! $request->boolean('allow_duplicate')) {
            return response()->json([
                'message' => 'Такая игра уже добавлена.',
                'duplicate' => $this->duplicates->details($duplicate),
            ], 409);
        }

        $game = $this->games->add($gameList, $catalogGame, allowDuplicate: true);
        if (! $game) {
            return response()->json(['message' => 'Не удалось добавить игру.'], 422);
        }

        return response()->json([
            'message' => 'Игра добавлена.',
            'game_id' => $game->id,
        ], 201);
    }
}
