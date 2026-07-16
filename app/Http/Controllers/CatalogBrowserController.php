<?php

namespace App\Http\Controllers;

use App\Models\GameList;
use App\Services\CatalogGameCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogBrowserController extends Controller
{
    public function __construct(private readonly CatalogGameCache $cache) {}

    public function index(Request $request, GameList $gameList): View
    {
        $this->authorizeOwner($request, $gameList);
        $query = trim((string) $request->query('q'));
        $games = $this->cache->paginate($query);

        return view('catalog.index', $this->viewData($request, $gameList, $games, $query));
    }

    public function results(Request $request, GameList $gameList): JsonResponse
    {
        $this->authorizeOwner($request, $gameList);
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = trim((string) ($validated['q'] ?? ''));
        $games = $this->cache->paginate($query);
        $data = $this->viewData($request, $gameList, $games, $query);

        return $this->resultsResponse($games, $data);
    }

    public function search(Request $request): View
    {
        $query = trim((string) $request->query('q'));
        $games = $this->cache->paginate($query);

        return view('catalog.index', $this->viewData($request, null, $games, $query));
    }

    public function searchResults(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = trim((string) ($validated['q'] ?? ''));
        $games = $this->cache->paginate($query);
        $data = $this->viewData($request, null, $games, $query);

        return $this->resultsResponse($games, $data);
    }

    private function resultsResponse($games, array $data): JsonResponse
    {
        return response()->json([
            'count' => $games->count(),
            'next_page' => $games->hasMorePages() ? $games->currentPage() + 1 : null,
            'html' => view('catalog._cards', $data)->render(),
        ]);
    }

    private function viewData(Request $request, ?GameList $gameList, $games, string $query): array
    {
        return [
            'gameList' => $gameList,
            'games' => $games,
            'query' => $query,
            'userLists' => $gameList === null && $request->user()
                ? $request->user()->gameLists()->latest()->get()
                : collect(),
            'addedHltbIds' => $gameList?->games()
                ->whereNotNull('hltb_id')
                ->pluck('hltb_id')
                ->mapWithKeys(fn ($id): array => [(string) $id => true])
                ->all() ?? [],
            'addedTitles' => $gameList?->games()
                ->pluck('normalized_title')
                ->mapWithKeys(fn ($title): array => [$title => true])
                ->all() ?? [],
        ];
    }

    private function authorizeOwner(Request $request, GameList $gameList): void
    {
        abort_unless($gameList->user_id === $request->user()->id, 403);
    }
}
