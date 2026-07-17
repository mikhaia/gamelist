<?php

namespace App\Http\Controllers;

use App\Services\CatalogGameCache;
use App\Services\RawgCatalogBrowser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class RawgCatalogSearchController extends Controller
{
    public function __construct(
        private readonly RawgCatalogBrowser $rawg,
        private readonly CatalogGameCache $cache,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/'],
            'platform' => ['nullable', 'integer', 'min:1'],
        ]);
        $genre = $validated['genre'] ?? null;
        $platform = isset($validated['platform']) ? (int) $validated['platform'] : null;

        abort_if($genre === null && $platform === null, 422, 'RAWG search requires a genre or platform filter.');

        try {
            $games = $this->rawg->search(
                trim((string) ($validated['q'] ?? '')),
                $genre,
                $platform,
            );

            return response()->json([
                'count' => $this->cache->storeRawg($games),
            ]);
        } catch (Throwable $exception) {
            Log::warning('RAWG catalog search failed.', ['message' => $exception->getMessage()]);

            return response()->json([
                'message' => 'Каталог RAWG сейчас недоступен.',
            ], 503);
        }
    }
}
