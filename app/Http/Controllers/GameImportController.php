<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Enums\Platform;
use App\Models\CatalogGame;
use App\Models\GameList;
use App\Services\CatalogGameListAdder;
use App\Services\GameImportParser;
use App\Services\GameTitleNormalizer;
use App\Services\ImportCatalogMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GameImportController extends Controller
{
    public function __construct(
        private readonly CatalogGameListAdder $catalogGames,
        private readonly ImportCatalogMatcher $catalogMatcher,
        private readonly GameImportParser $parser,
        private readonly GameTitleNormalizer $normalizer,
    ) {}

    public function create(Request $request, GameList $gameList): View
    {
        $this->authorizeOwner($request, $gameList);

        return view('imports.create', $this->viewData($gameList));
    }

    public function preview(Request $request, GameList $gameList): View
    {
        $this->authorizeOwner($request, $gameList);
        $validated = $request->validate(['games_text' => ['required', 'string', 'max:30000']]);
        $items = $this->parser->parse($validated['games_text']);

        if (count($items) > 100) {
            throw ValidationException::withMessages(['games_text' => __('app.errors.import_limit')]);
        }

        $existing = $gameList->games()->whereIn('normalized_title', array_column($items, 'normalized_title'))
            ->pluck('normalized_title')->flip();

        $items = array_map(function (array $item) use ($existing): array {
            $item['duplicate_existing'] = $existing->has($item['normalized_title']);
            $item['catalog_suggestions'] = $item['duplicate_in_input'] || $item['duplicate_existing']
                ? []
                : $this->catalogMatcher->forTitle($item['title']);

            return $item;
        }, $items);

        return view('imports.create', array_merge($this->viewData($gameList), [
            'items' => $items,
            'gamesText' => $validated['games_text'],
        ]));
    }

    public function store(Request $request, GameList $gameList): RedirectResponse
    {
        $this->authorizeOwner($request, $gameList);
        $validated = $request->validate([
            'items' => ['required', 'array', 'max:100'],
            'items.*.selected' => ['nullable', 'boolean'],
            'items.*.title' => ['required', 'string', 'max:255'],
            'items.*.catalog_game_id' => ['nullable', 'integer', Rule::exists('catalog_games', 'id')],
            'status' => ['required', Rule::in($gameList->availableStatusValues())],
            'platform' => ['required', Rule::enum(Platform::class)],
        ]);

        $items = collect($validated['items'])->filter(fn (array $item): bool => (bool) ($item['selected'] ?? false));
        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => 'Выберите хотя бы одну новую игру.']);
        }

        $existingGames = $gameList->games()->get(['normalized_title', 'catalog_game_id']);
        $existing = $existingGames->pluck('normalized_title')->flip();
        $existingCatalogIds = $existingGames->pluck('catalog_game_id')->filter()->flip();
        $catalogGameIds = $items->pluck('catalog_game_id')->filter()->unique()->values()->all();
        $catalogGames = CatalogGame::query()
            ->whereKey($catalogGameIds)
            ->get()
            ->keyBy('id');
        $status = GameStatus::from($validated['status']);
        $platform = Platform::from($validated['platform']);
        $created = 0;

        DB::transaction(function () use ($items, $gameList, $existing, $existingCatalogIds, $catalogGames, $status, $platform, &$created): void {
            foreach ($items as $item) {
                $catalogGame = isset($item['catalog_game_id'])
                    ? $catalogGames->get((int) $item['catalog_game_id'])
                    : null;
                $title = $catalogGame?->title ?? trim($item['title']);
                $normalized = $this->normalizer->normalize($title);
                if ($title === '' || $existing->has($normalized) || ($catalogGame && $existingCatalogIds->has($catalogGame->id))) {
                    continue;
                }

                $game = $catalogGame
                    ? $this->catalogGames->add($gameList, $catalogGame, $status, allowDuplicate: true, platform: $platform)
                    : $gameList->games()->create([
                        'title' => $title,
                        'normalized_title' => $normalized,
                        'status' => $status,
                        'platform' => $platform,
                    ]);
                if (! $game) {
                    continue;
                }

                $existing->put($normalized, true);
                if ($catalogGame) {
                    $existingCatalogIds->put($catalogGame->id, true);
                }
                $created++;
            }
        });

        return redirect()->route('lists.show', $gameList)
            ->with('success', __('app.messages.imported', ['count' => $created]));
    }

    private function viewData(GameList $gameList): array
    {
        return [
            'gameList' => $gameList,
            'statuses' => $gameList->availableStatuses(),
            'platforms' => Platform::cases(),
            'items' => null,
            'gamesText' => '',
        ];
    }

    private function authorizeOwner(Request $request, GameList $gameList): void
    {
        abort_unless($gameList->user_id === $request->user()->id, 403);
    }
}
