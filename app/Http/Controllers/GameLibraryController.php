<?php

namespace App\Http\Controllers;

use App\Models\CatalogGame;
use App\Models\GameList;
use App\Services\CatalogGameListAdder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GameLibraryController extends Controller
{
    public function __construct(private readonly CatalogGameListAdder $games) {}

    public function store(Request $request, CatalogGame $catalogGame): RedirectResponse
    {
        $validated = $request->validate([
            'game_list_id' => [
                'required',
                'integer',
                Rule::exists('game_lists', 'id')->where('user_id', $request->user()->id),
            ],
        ]);
        $gameList = GameList::query()->findOrFail($validated['game_list_id']);

        if (! $this->games->add($gameList, $catalogGame)) {
            throw ValidationException::withMessages([
                'game_list_id' => __('app.errors.game_duplicate'),
            ]);
        }

        return redirect()->route('games.show', $catalogGame)
            ->with('success', __('app.messages.game_added_to_list', ['list' => $gameList->name]));
    }
}
