<?php

namespace App\Http\Controllers;

use App\Enums\Platform;
use App\Models\Game;
use App\Models\GameList;
use App\Services\CatalogGameCache;
use App\Services\CatalogGameResolver;
use App\Services\CoverImageService;
use App\Services\GameDuplicateDetector;
use App\Services\GameTitleNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GameController extends Controller
{
    public function __construct(
        private readonly CatalogGameCache $catalogCache,
        private readonly CatalogGameResolver $catalogGames,
        private readonly CoverImageService $covers,
        private readonly GameDuplicateDetector $duplicates,
        private readonly GameTitleNormalizer $normalizer,
    ) {}

    public function create(Request $request, GameList $gameList): View
    {
        $this->authorizeList($request, $gameList);

        return $this->formView($request, $gameList, new Game([
            'status' => $gameList->defaultStatus(),
            'platform' => $gameList->default_platform,
        ]));
    }

    public function store(Request $request, GameList $gameList): RedirectResponse
    {
        $this->authorizeList($request, $gameList);
        $validated = $this->validated($request, $gameList);
        $validated['normalized_title'] = $this->normalizer->normalize($validated['title']);
        $catalogGame = $this->catalogGames->resolve(new Game($validated));

        if (! $request->boolean('allow_duplicate')) {
            $duplicate = $this->duplicates->find(
                $request->user()->id,
                $validated['title'],
                $catalogGame?->id,
            );

            if ($duplicate) {
                return back()
                    ->withInput()
                    ->with('duplicateGame', $this->duplicates->details($duplicate));
            }
        }

        $validated['catalog_game_id'] = $catalogGame?->id;
        $validated['cover_path'] = $this->storeCover($request);
        $validated['source_cover_url'] = $request->input('cover_url') ?: $request->input('catalog_cover_url');
        $gameList->games()->create($validated);

        return redirect()->route('lists.show', $gameList)->with('success', __('app.messages.game_created'));
    }

    public function edit(Request $request, Game $game): View
    {
        $this->authorizeGame($request, $game);

        return $this->formView($request, $game->gameList, $game);
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        $this->authorizeGame($request, $game);
        $gameList = $this->selectedList($request, $game->gameList);
        $validated = $this->validated($request, $gameList, $game);
        $validated['game_list_id'] = $gameList->id;
        $validated['normalized_title'] = $this->normalizer->normalize($validated['title']);

        if ($request->hasFile('cover') || $request->filled('cover_url') || $request->filled('catalog_cover_url')) {
            $validated['cover_path'] = $this->storeCover($request, $game->cover_path);
            $validated['source_cover_url'] = $request->input('cover_url') ?: $request->input('catalog_cover_url');
        }

        $game->unsetRelation('gameList');
        $game->update($validated);

        return redirect()->route('lists.show', $gameList)->with('success', __('app.messages.game_updated'));
    }

    public function status(Request $request, Game $game): JsonResponse|RedirectResponse
    {
        $this->authorizeGame($request, $game);
        $validated = $request->validate([
            'status' => ['required', Rule::in($game->gameList->availableStatusValues())],
        ]);
        $game->update($validated);

        if ($request->expectsJson()) {
            $game->refresh();

            return response()->json([
                'message' => __('app.messages.status_updated'),
                'status' => $game->status->value,
                'label' => $game->status->label(),
                'icon' => $game->status->icon(),
                'started_at' => $game->started_at?->format('Y-m-d'),
                'completed_at' => $game->completed_at?->format('Y-m-d'),
            ]);
        }

        return back()->with('success', __('app.messages.status_updated'));
    }

    public function destroy(Request $request, Game $game): RedirectResponse
    {
        $this->authorizeGame($request, $game);
        $list = $game->gameList;
        $game->delete();

        return redirect()->route('lists.show', $list)->with('success', __('app.messages.game_deleted'));
    }

    private function formView(Request $request, GameList $gameList, Game $game): View
    {
        $results = [];
        $query = trim((string) $request->query('q'));

        if ($query !== '') {
            $results = $this->catalogCache->search($query);
        }

        return view('games.form', [
            'gameList' => $gameList,
            'game' => $game,
            'statuses' => $gameList->availableStatuses(),
            'gameLists' => $game->exists
                ? $request->user()->gameLists()->orderBy('name')->get()
                : collect(),
            'platforms' => Platform::cases(),
            'results' => $results,
            'query' => $query,
        ]);
    }

    private function validated(Request $request, GameList $gameList, ?Game $game = null): array
    {
        $validated = $request->validate([
            'title' => [
                'required', 'string', 'max:255',
            ],
            'status' => ['required', Rule::in($gameList->availableStatusValues())],
            'platform' => ['required', Rule::enum(Platform::class)],
            'started_at' => ['nullable', 'date'],
            'completed_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'hltb_id' => ['nullable', 'integer', 'min:1'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'cover_url' => ['nullable', 'url:http,https', 'max:2048'],
            'catalog_cover_url' => ['nullable', 'url:http,https', 'max:2048'],
            'source_cover_url' => ['nullable', 'url:http,https', 'max:2048'],
            'main_story_minutes' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'main_extra_minutes' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'completionist_minutes' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [
            'title.unique' => __('app.errors.game_duplicate'),
        ]);

        if ($game) {
            $duplicate = $gameList->games()
                ->where('normalized_title', $this->normalizer->normalize($validated['title']))
                ->whereKeyNot($game->id)
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages(['title' => __('app.errors.game_duplicate')]);
            }
        }

        return $validated;
    }

    private function storeCover(Request $request, ?string $oldPath = null): ?string
    {
        if ($request->hasFile('cover')) {
            return $this->covers->storeGameCover($request->file('cover'), $oldPath);
        }

        $url = $request->input('cover_url') ?: $request->input('catalog_cover_url');
        if ($url) {
            return $this->covers->storeUrl($url, $oldPath);
        }

        return $oldPath;
    }

    private function selectedList(Request $request, GameList $currentList): GameList
    {
        if (! $request->filled('game_list_id')) {
            return $currentList;
        }

        $validated = $request->validate([
            'game_list_id' => [
                'required',
                Rule::exists('game_lists', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        return $request->user()->gameLists()->findOrFail($validated['game_list_id']);
    }

    private function authorizeList(Request $request, GameList $gameList): void
    {
        abort_unless($gameList->user_id === $request->user()->id, 403);
    }

    private function authorizeGame(Request $request, Game $game): void
    {
        abort_unless($game->gameList->user_id === $request->user()->id, 403);
    }
}
