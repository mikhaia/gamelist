<?php

namespace App\Http\Controllers;

use App\Enums\Platform;
use App\Models\GameList;
use App\Services\GameImportParser;
use App\Services\GameTitleNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class GameImportController extends Controller
{
    public function __construct(
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
            'titles' => ['required', 'array', 'max:100'],
            'titles.*' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in($gameList->availableStatusValues())],
            'platform' => ['required', Rule::enum(Platform::class)],
        ]);

        $existing = $gameList->games()->pluck('normalized_title')->flip();
        $created = 0;

        DB::transaction(function () use ($validated, $gameList, $existing, &$created): void {
            foreach ($validated['titles'] as $title) {
                $title = trim($title);
                $normalized = $this->normalizer->normalize($title);
                if ($title === '' || $existing->has($normalized)) {
                    continue;
                }

                $gameList->games()->create([
                    'title' => $title,
                    'normalized_title' => $normalized,
                    'status' => $validated['status'],
                    'platform' => $validated['platform'],
                ]);
                $existing->put($normalized, true);
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
