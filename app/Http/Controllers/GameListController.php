<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Enums\Platform;
use App\Models\GameList;
use App\Services\CoverImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GameListController extends Controller
{
    public function __construct(private readonly CoverImageService $covers) {}

    public function index(Request $request): View
    {
        $lists = $request->user()->gameLists()->withCount('games')->latest()->get();

        return view('lists.index', compact('lists'));
    }

    public function create(): View
    {
        return view('lists.form', ['gameList' => new GameList, 'platforms' => Platform::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated['slug'] = $this->makeSlug($request, $validated['name']);
        $validated['is_public'] = $request->boolean('is_public');
        if ($request->hasFile('cover')) {
            $validated['cover_path'] = $this->covers->storeUpload($request->file('cover'), null, 'list-covers', 1800, 1200);
        }
        $gameList = $request->user()->gameLists()->create($validated);

        return redirect()->route('lists.show', $gameList)->with('success', __('app.messages.list_created'));
    }

    public function show(Request $request, GameList $gameList): View
    {
        $this->authorizeOwner($request, $gameList);
        $gameList->load('user');
        $totalGames = $gameList->games()->count();
        $selectedStatuses = $this->statuses($request);
        $games = $gameList->games()
            ->when($selectedStatuses !== [], fn ($query) => $query->whereIn('status', $selectedStatuses))
            ->get();
        $gameList->setRelation('games', $games);

        return view('lists.show', [
            'gameList' => $gameList,
            'statuses' => GameStatus::cases(),
            'selectedStatuses' => $selectedStatuses,
            'totalGames' => $totalGames,
        ]);
    }

    public function edit(Request $request, GameList $gameList): View
    {
        $this->authorizeOwner($request, $gameList);

        return view('lists.form', compact('gameList') + ['platforms' => Platform::cases()]);
    }

    public function update(Request $request, GameList $gameList): RedirectResponse
    {
        $this->authorizeOwner($request, $gameList);
        $validated = $this->validated($request, $gameList);
        $validated['slug'] = $this->makeSlug($request, $validated['name']);
        $validated['is_public'] = $request->boolean('is_public');
        if ($request->hasFile('cover')) {
            $validated['cover_path'] = $this->covers->storeUpload(
                $request->file('cover'),
                $gameList->cover_path,
                'list-covers',
                1800,
                1200,
            );
        }
        $gameList->update($validated);

        return redirect()->route('lists.show', $gameList)->with('success', __('app.messages.list_updated'));
    }

    public function destroy(Request $request, GameList $gameList): RedirectResponse
    {
        $this->authorizeOwner($request, $gameList);
        $gameList->games()->whereNotNull('cover_path')->pluck('cover_path')->each(
            fn (string $path) => Storage::disk('public')->delete($path)
        );
        if ($gameList->cover_path) {
            Storage::disk('public')->delete($gameList->cover_path);
        }
        $gameList->delete();

        return redirect()->route('lists.index')->with('success', __('app.messages.list_deleted'));
    }

    public function display(Request $request, GameList $gameList): RedirectResponse
    {
        $this->authorizeOwner($request, $gameList);
        $validated = $request->validate(['display_mode' => ['required', Rule::in(['cards', 'compact'])]]);
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
            'default_platform' => ['required', Rule::enum(Platform::class)],
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
    private function statuses(Request $request): array
    {
        $allowed = array_column(GameStatus::cases(), 'value');

        return array_values(array_intersect((array) $request->query('status', []), $allowed));
    }
}
