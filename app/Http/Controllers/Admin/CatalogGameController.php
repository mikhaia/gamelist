<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogGame;
use App\Services\CatalogFilterOptions;
use App\Services\GameTitleNormalizer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogGameController extends Controller
{
    public function __construct(
        private readonly CatalogFilterOptions $filterOptions,
        private readonly GameTitleNormalizer $normalizer,
    ) {}

    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/'],
            'age_rating' => ['nullable', 'string', 'max:100'],
            'platform' => ['nullable', 'integer', 'min:1'],
            'sort' => ['nullable', Rule::in(['title', 'updated_at', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $genre = $validated['genre'] ?? null;
        $ageRating = $validated['age_rating'] ?? null;
        $platform = isset($validated['platform']) ? (int) $validated['platform'] : null;
        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? ($sort === 'title' ? 'asc' : 'desc');
        $sortColumn = $sort === 'title' ? 'normalized_title' : $sort;

        $games = CatalogGame::query()
            ->when($query !== '', fn ($games) => $games->where('normalized_title', 'like', '%'.$this->normalizer->normalize($query).'%'))
            ->when($genre, fn ($games, string $value) => $games->whereJsonContains('genre_slugs', $value))
            ->when($ageRating, fn ($games, string $value) => $games->where('age_rating', $value))
            ->when($platform, fn ($games, int $value) => $games->whereJsonContains('platform_ids', $value))
            ->orderBy($sortColumn, $direction)
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.games.index', [
            'games' => $games,
            'query' => $query,
            'genre' => $genre,
            'ageRating' => $ageRating,
            'platform' => $platform,
            'sort' => $sort,
            'direction' => $direction,
            'filterOptions' => $this->filterOptions->all(),
            'ageRatings' => CatalogGame::query()
                ->whereNotNull('age_rating')
                ->where('age_rating', '!=', '')
                ->distinct()
                ->orderBy('age_rating')
                ->pluck('age_rating'),
        ]);
    }
}
