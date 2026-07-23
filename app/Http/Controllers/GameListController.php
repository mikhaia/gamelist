<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Enums\Platform;
use App\Models\GameList;
use App\Services\CoverImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GameListController extends Controller
{
    public function __construct(private readonly CoverImageService $covers) {}

    public function index(Request $request): View
    {
        $lists = $request->user()->gameLists()
            ->withCount('games')
            ->orderByLatestGameUpdate()
            ->get();

        return view('lists.index', compact('lists'));
    }

    public function create(): View
    {
        return view('lists.form', [
            'gameList' => new GameList,
            'platforms' => Platform::cases(),
            'statuses' => GameStatus::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['slug'] = $this->makeSlug($request, $validated['name']);
        $validated['is_public'] = $request->boolean('is_public');
        if ($request->hasFile('cover')) {
            $validated['cover_path'] = $this->covers->storeListCover($request->file('cover'));
        } elseif ($request->filled('cover_url')) {
            $validated['cover_path'] = $this->covers->storeListUrl($request->string('cover_url')->toString());
        }
        $gameList = $request->user()->gameLists()->create($validated);

        return redirect()->route('lists.show', $gameList)->with('success', __('app.messages.list_created'));
    }

    public function show(Request $request, GameList $gameList): View
    {
        $this->authorizeOwner($request, $gameList);
        $gameList->load('user');
        $totalGames = $gameList->games()->count();
        $selectedStatuses = $this->statuses($request, $gameList);
        $sort = $request->query('sort') === 'completed_at' ? 'completed_at' : 'added_at';
        $games = $gameList->games()
            ->when($selectedStatuses !== [], fn ($query) => $query->whereIn('status', $selectedStatuses))
            ->sortedForList($sort)
            ->get();
        $gameList->setRelation('games', $games);

        return view('lists.show', [
            'gameList' => $gameList,
            'statuses' => $gameList->availableStatuses(),
            'selectedStatuses' => $selectedStatuses,
            'sort' => $sort,
            'totalGames' => $totalGames,
        ]);
    }

    public function edit(Request $request, GameList $gameList): View
    {
        $this->authorizeOwner($request, $gameList);

        return view('lists.form', compact('gameList') + [
            'platforms' => Platform::cases(),
            'statuses' => GameStatus::cases(),
        ]);
    }

    public function update(Request $request, GameList $gameList): RedirectResponse
    {
        $this->authorizeOwner($request, $gameList);
        $validated = $this->validated($request, $gameList);
        $this->ensureStatusesAreUnused($gameList, $validated['available_statuses']);
        $validated['slug'] = $this->makeSlug($request, $validated['name']);
        $validated['is_public'] = $request->boolean('is_public');
        if ($request->hasFile('cover')) {
            $validated['cover_path'] = $this->covers->storeListCover($request->file('cover'), $gameList->cover_path);
        } elseif ($request->filled('cover_url')) {
            $validated['cover_path'] = $this->covers->storeListUrl($request->string('cover_url')->toString(), $gameList->cover_path);
        }
        $gameList->update($validated);

        return redirect()->route('lists.show', $gameList)->with('success', __('app.messages.list_updated'));
    }

    public function destroy(Request $request, GameList $gameList): RedirectResponse
    {
        $this->authorizeOwner($request, $gameList);
        $gameList->games()->get()->each->delete();
        if ($gameList->cover_path) {
            Storage::disk('public')->delete($gameList->cover_path);
        }
        $gameList->delete();

        return redirect()->route('lists.index')->with('success', __('app.messages.list_deleted'));
    }

    public function display(Request $request, GameList $gameList): RedirectResponse
    {
        $this->authorizeOwner($request, $gameList);
        $validated = $request->validate(['display_mode' => ['required', Rule::in(['cards', 'compact', 'board'])]]);
        $gameList->update($validated);

        return back();
    }

    private function validated(Request $request, ?GameList $gameList = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('game_lists', 'slug')->where('user_id', $request->user()->id)->ignore($gameList),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover' => ['nullable', 'image', 'max:8192'],
            'cover_url' => ['nullable', 'url:http,https', 'max:2048'],
            'default_platform' => ['required', Rule::enum(Platform::class)],
            'available_statuses' => ['required', 'array', 'min:1', 'max:'.count(GameStatus::cases())],
            'available_statuses.*' => ['required', 'distinct', Rule::enum(GameStatus::class)],
            'is_public' => ['nullable', 'boolean'],
        ]);
    }

    private function makeSlug(Request $request, string $name): string
    {
        $slug = $request->filled('slug') ? $request->string('slug')->lower()->toString() : Str::slug($name);
        if ($slug === '') {
            $slug = 'game-list';
        }

        $base = $slug;
        $suffix = 2;
        while ($request->user()->gameLists()->where('slug', $slug)->when($request->route('gameList'), fn ($q, $list) => $q->whereKeyNot($list->id))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function authorizeOwner(Request $request, GameList $gameList): void
    {
        abort_unless($gameList->user_id === $request->user()->id, 403);
    }

    /** @return array<int, string> */
    private function statuses(Request $request, ?GameList $gameList = null): array
    {
        $allowed = $gameList?->availableStatusValues() ?? array_column(GameStatus::cases(), 'value');

        return array_values(array_intersect((array) $request->query('status', []), $allowed));
    }

    /** @param array<int, string> $availableStatuses */
    private function ensureStatusesAreUnused(GameList $gameList, array $availableStatuses): void
    {
        $usedDisabledStatuses = $gameList->games()
            ->reorder()
            ->whereNotIn('status', $availableStatuses)
            ->distinct()
            ->pluck('status')
            ->map(fn (GameStatus|string $status): string => ($status instanceof GameStatus ? $status : GameStatus::from($status))->label())
            ->implode(', ');

        if ($usedDisabledStatuses !== '') {
            throw ValidationException::withMessages([
                'available_statuses' => __('app.errors.statuses_in_use', ['statuses' => $usedDisabledStatuses]),
            ]);
        }
    }
}
