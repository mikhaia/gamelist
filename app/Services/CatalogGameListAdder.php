<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\CatalogGame;
use App\Models\Game;
use App\Models\GameList;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class CatalogGameListAdder
{
    public function __construct(
        private readonly CoverImageService $covers,
        private readonly GameDuplicateDetector $duplicates,
    ) {}

    public function add(GameList $gameList, CatalogGame $catalogGame, ?GameStatus $status = null, bool $allowDuplicate = false): ?Game
    {
        if (! $allowDuplicate && $this->duplicate($gameList, $catalogGame)) {
            return null;
        }

        $coverPath = $this->storeCover($catalogGame);

        try {
            return $gameList->games()->create([
                'catalog_game_id' => $catalogGame->id,
                'title' => $catalogGame->title,
                'normalized_title' => $catalogGame->normalized_title,
                'status' => $status ?? $gameList->defaultStatus(),
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

            return null;
        }
    }

    public function duplicate(GameList $gameList, CatalogGame $catalogGame): ?Game
    {
        return $this->duplicates->find($gameList->user_id, $catalogGame->title, $catalogGame->id);
    }

    private function storeCover(CatalogGame $catalogGame): ?string
    {
        if ($catalogGame->cover_url) {
            try {
                return $this->covers->storeUrl($catalogGame->cover_url);
            } catch (Throwable $exception) {
                Log::warning('Catalog cover download failed', [
                    'catalog_game_id' => $catalogGame->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $sourcePath = $catalogGame->games()
            ->whereNotNull('cover_path')
            ->latest()
            ->value('cover_path');
        if (! $sourcePath || ! Storage::disk('public')->exists($sourcePath)) {
            return null;
        }

        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION) ?: 'webp';
        $targetPath = 'game-covers/'.Str::uuid().'.'.$extension;

        try {
            Storage::disk('public')->makeDirectory('game-covers');

            return Storage::disk('public')->copy($sourcePath, $targetPath) ? $targetPath : null;
        } catch (Throwable $exception) {
            Log::warning('Local catalog cover copy failed', [
                'catalog_game_id' => $catalogGame->id,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
