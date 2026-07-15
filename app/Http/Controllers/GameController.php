<?php

namespace App\Http\Controllers;

use App\Enums\Platform;
use App\Models\Game;
use App\Models\GameList;
use App\Services\CatalogGameCache;
use App\Services\CoverImageService;
use App\Services\GameTitleNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GameController extends Controller
{
    public function __construct(
        private readonly CatalogGameCache $catalogCache,
        private readonly CoverImageService $covers,
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
        $validated = $this->validated($request, $game->gameList, $game);
        $validated['normalized_title'] = $this->normalizer->normalize($validated['title']);

        if ($request->hasFile('cover') || $request->filled('cover_url') || $request->filled('catalog_cover_url')) {
            $validated['cover_path'] = $this->storeCover($request, $game->cover_path);
            $validated['source_cover_url'] = $request->input('cover_url') ?: $request->input('catalog_cover_url');
        }

        $game->update($validated);

        return redirect()->route('lists.show', $game->gameList)->with('success', __('app.messages.game_updated'));
    }

    public function status(Request $request, Game $game): RedirectResponse
    {
        $this->authorizeGame($request, $game);
        $validated = $request->validate([
            'status' => ['required', Rule::in($game->gameList->availableStatusValues())],
        ]);
        $game->update($validated);

        return back()->with('success', __('app.messages.status_updated'));
    }

    public function destroy(Request $request, Game $game): RedirectResponse
    {
        $this->authorizeGame($request, $game);
        $list = $game->gameList;
        if ($game->cover_path) {
            Storage::disk('public')->delete($game->cover_path);
        }
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

        $duplicate = $gameList->games()
            ->where('normalized_title', $this->normalizer->normalize($validated['title']))
            ->when($game, fn ($query) => $query->whereKeyNot($game->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages(['title' => __('app.errors.game_duplicate')]);
        }

        return $validated;
    }

    private function storeCover(Request $request, ?string $oldPath = null): ?string
    {
        if ($request->hasFile('cover')) {
            return $this->covers->storeUpload($request->file('cover'), $oldPath);
        }

        $url = $request->input('cover_url') ?: $request->input('catalog_cover_url');
        if ($url) {
            return $this->covers->storeUrl($url, $oldPath);
        }

        return $oldPath;
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
