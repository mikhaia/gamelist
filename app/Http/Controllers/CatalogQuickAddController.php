<?php

namespace App\Http\Controllers;

use App\Models\CatalogGame;
use App\Models\GameList;
use App\Services\CoverImageService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CatalogQuickAddController extends Controller
{
    public function __construct(private readonly CoverImageService $covers) {}

    public function __invoke(Request $request, GameList $gameList, CatalogGame $catalogGame): JsonResponse
    {
        abort_unless($gameList->user_id === $request->user()->id, 403);

        $exists = $gameList->games()
            ->where(function ($query) use ($catalogGame): void {
                $query->where('normalized_title', $catalogGame->normalized_title)
                    ->orWhere('hltb_id', $catalogGame->hltb_id);
            })
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Игра уже есть в этом списке.'], 409);
        }

        $coverPath = null;
        if ($catalogGame->cover_url) {
            try {
                $coverPath = $this->covers->storeUrl($catalogGame->cover_url);
            } catch (Throwable $exception) {
                Log::warning('Catalog cover download failed', [
                    'catalog_game_id' => $catalogGame->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        try {
            $game = $gameList->games()->create([
                'title' => $catalogGame->title,
                'normalized_title' => $catalogGame->normalized_title,
                'status' => $gameList->defaultStatus(),
                'platform' => $gameList->default_platform,
                'hltb_id' => $catalogGame->hltb_id,
                'cover_path' => $coverPath,
                'source_cover_url' => $catalogGame->cover_url,
                'main_story_minutes' => $catalogGame->main_story_minutes,
                'completionist_minutes' => $catalogGame->completionist_minutes,
            ]);
        } catch (QueryException) {
            if ($coverPath) {
                Storage::disk('public')->delete($coverPath);
            }

            return response()->json(['message' => 'Игра уже есть в этом списке.'], 409);
        }

        return response()->json([
            'message' => 'Игра добавлена.',
            'game_id' => $game->id,
        ], 201);
    }
}
