<?php

namespace App\Http\Controllers;

use App\Enums\GameStatus;
use App\Models\CatalogGame;
use App\Models\GameList;
use App\Services\CatalogGameListAdder;
use App\Services\GameDuplicateDetector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GameLibraryController extends Controller
{
    public function __construct(
        private readonly CatalogGameListAdder $games,
        private readonly GameDuplicateDetector $duplicates,
    ) {}

    public function store(Request $request, CatalogGame $catalogGame): RedirectResponse
    {
        $validated = $request->validate([
            'game_list_id' => [
                'required',
                'integer',
                Rule::exists('game_lists', 'id')->where('user_id', $request->user()->id),
            ],
            'status' => ['required', Rule::enum(GameStatus::class)],
        ]);
        $gameList = GameList::query()->findOrFail($validated['game_list_id']);
        $status = GameStatus::from($validated['status']);

        if (! in_array($status->value, $gameList->availableStatusValues(), true)) {
            throw ValidationException::withMessages([
                'status' => __('app.errors.status_not_available'),
            ]);
        }

        $duplicate = $this->games->duplicate($gameList, $catalogGame);
        if ($duplicate && ! $request->boolean('allow_duplicate')) {
            return back()
                ->withInput()
                ->with('duplicateGame', $this->duplicates->details($duplicate));
        }

        if (! $this->games->add($gameList, $catalogGame, $status, true)) {
            throw ValidationException::withMessages([
                'game_list_id' => __('app.errors.game_duplicate'),
            ]);
        }

        return redirect()->route('games.show', $catalogGame)
            ->with('success', __('app.messages.game_added_to_list', ['list' => $gameList->name]));
    }
}
