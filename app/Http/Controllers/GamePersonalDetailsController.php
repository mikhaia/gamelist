<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Services\GameAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GamePersonalDetailsController extends Controller
{
    public function __construct(private readonly GameAccess $access) {}

    public function update(Request $request, Game $game): RedirectResponse
    {
        $this->access->authorizeOwner($request->user(), $game);
        $validated = $request->validate([
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $game->update($validated);

        return back()->with('success', 'Личные данные записи обновлены.');
    }
}
