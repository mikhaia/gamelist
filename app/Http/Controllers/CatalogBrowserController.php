<?php

namespace App\Http\Controllers;

use App\Models\GameList;
use App\Services\CatalogFilterOptions;
use App\Services\CatalogGameCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogBrowserController extends Controller
{
    public function __construct(
        private readonly CatalogGameCache $cache,
        private readonly CatalogFilterOptions $filterOptions,
    ) {}

    public function index(Request $request, GameList $gameList): View
    {
        $this->authorizeOwner($request, $gameList);
        $criteria = $this->criteria($request);
        $games = $this->cache->paginate($criteria['query'], $criteria['genre'], $criteria['platform']);
        [$criteria, $options] = $this->pageFilters($criteria);

        return view('catalog.index', [
            ...$this->viewData($request, $gameList, $games, $criteria),
            'filterOptions' => $options,
        ]);
    }

    public function results(Request $request, GameList $gameList): JsonResponse
    {
        $this->authorizeOwner($request, $gameList);
        $criteria = $this->criteria($request, true);
        $games = $this->cache->paginate($criteria['query'], $criteria['genre'], $criteria['platform']);
        $data = $this->viewData($request, $gameList, $games, $criteria);

        return $this->resultsResponse($games, $data);
    }

    public function search(Request $request): View
    {
        $criteria = $this->criteria($request);
        $games = $this->cache->paginate($criteria['query'], $criteria['genre'], $criteria['platform']);
        [$criteria, $options] = $this->pageFilters($criteria);

        return view('catalog.index', [
            ...$this->viewData($request, null, $games, $criteria),
            'filterOptions' => $options,
        ]);
    }

    public function searchResults(Request $request): JsonResponse
    {
        $criteria = $this->criteria($request, true);
        $games = $this->cache->paginate($criteria['query'], $criteria['genre'], $criteria['platform']);
        $data = $this->viewData($request, null, $games, $criteria);

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

    /** @param array{query: string, genre: ?string, genreName: ?string, platform: ?int, platformName: ?string} $criteria */
    private function viewData(Request $request, ?GameList $gameList, $games, array $criteria): array
    {
        return [
            'gameList' => $gameList,
            'games' => $games,
            'query' => $criteria['query'],
            'filters' => $criteria,
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

    /** @return array{query: string, genre: ?string, genreName: ?string, platform: ?int, platformName: ?string} */
    private function criteria(Request $request, bool $withPage = false): array
    {
        $rules = [
            'q' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/'],
            'genre_name' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'integer', 'min:1'],
            'platform_name' => ['nullable', 'string', 'max:100'],
        ];
        if ($withPage) {
            $rules['page'] = ['nullable', 'integer', 'min:1'];
        }

        $validated = $request->validate($rules);
        $genre = $validated['genre'] ?? null;
        $platform = isset($validated['platform']) ? (int) $validated['platform'] : null;

        return [
            'query' => trim((string) ($validated['q'] ?? '')),
            'genre' => $genre,
            'genreName' => $genre ? trim((string) ($validated['genre_name'] ?? '')) ?: null : null,
            'platform' => $platform,
            'platformName' => $platform ? trim((string) ($validated['platform_name'] ?? '')) ?: null : null,
        ];
    }

    /**
     * @param  array{query: string, genre: ?string, genreName: ?string, platform: ?int, platformName: ?string}  $criteria
     * @return array{
     *     array{query: string, genre: ?string, genreName: ?string, platform: ?int, platformName: ?string},
     *     array{genres: array<int, array{value: string, label: string}>, platforms: array<int, array{value: int, label: string}>}
     * }
     */
    private function pageFilters(array $criteria): array
    {
        $options = $this->filterOptions->all();

        if ($criteria['genre'] && ! $criteria['genreName']) {
            $criteria['genreName'] = collect($options['genres'])
                ->firstWhere('value', $criteria['genre'])['label'] ?? $criteria['genre'];
        }

        if ($criteria['platform'] && ! $criteria['platformName']) {
            $criteria['platformName'] = collect($options['platforms'])
                ->firstWhere('value', $criteria['platform'])['label'] ?? "Платформа {$criteria['platform']}";
        }

        return [$criteria, $options];
    }

    private function authorizeOwner(Request $request, GameList $gameList): void
    {
        abort_unless($gameList->user_id === $request->user()->id, 403);
    }
}
