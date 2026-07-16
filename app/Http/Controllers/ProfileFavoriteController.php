<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProfileFavoriteController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        if (is_array($request->input('game_ids'))) {
            $request->merge([
                'game_ids' => collect($request->input('game_ids'))
                    ->filter(fn ($id): bool => filled($id))
                    ->values()
                    ->all(),
            ]);
        }

        $validated = $request->validate([
            'game_ids' => ['nullable', 'array', 'max:3'],
            'game_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $gameIds = collect($validated['game_ids'] ?? [])
            ->filter(fn ($id): bool => filled($id))
            ->map(fn ($id): int => (int) $id)
            ->values();
        $ownedCount = Game::query()
            ->whereKey($gameIds)
            ->whereHas('gameList', fn ($query) => $query->where('user_id', $request->user()->getKey()))
            ->count();

        if ($ownedCount !== $gameIds->count()) {
            throw ValidationException::withMessages([
                'game_ids' => __('app.errors.favorite_games_owner'),
            ]);
        }

        $sync = $gameIds->mapWithKeys(
            fn (int $gameId, int $index): array => [$gameId => ['sort_order' => $index]],
        )->all();
        $request->user()->favoriteGames()->sync($sync);

        return redirect()->route('profiles.show', $request->user()->login)
            ->with('success', __('app.messages.favorites_updated'));
    }
}
