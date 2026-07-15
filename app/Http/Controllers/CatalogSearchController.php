<?php

namespace App\Http\Controllers;

use App\Contracts\GameCatalog;
use App\Services\CatalogGameCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class CatalogSearchController extends Controller
{
    public function __construct(
        private readonly GameCatalog $catalog,
        private readonly CatalogGameCache $cache,
    ) {}

    public function cached(Request $request): JsonResponse
    {
        $query = $this->query($request);
        $results = $this->cache->search($query);

        return $this->resultsResponse($results, true);
    }

    public function fresh(Request $request): JsonResponse
    {
        $query = $this->query($request);

        try {
            $results = $this->catalog->search($query, 20);
            $this->cache->store($results);

            return $this->resultsResponse($results, false);
        } catch (Throwable $exception) {
            Log::warning('HowLongToBeat search failed', ['message' => $exception->getMessage()]);

            return response()->json([
                'message' => 'Внешний каталог сейчас недоступен.',
            ], 503);
        }
    }

    private function query(Request $request): string
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'max:255'],
        ]);

        return trim($validated['q']);
    }

    /** @param array<int, array<string, int|string|null>> $results */
    private function resultsResponse(array $results, bool $cached): JsonResponse
    {
        return response()->json([
            'count' => count($results),
            'html' => view('games._catalog_results', compact('results', 'cached'))->render(),
        ]);
    }
}
