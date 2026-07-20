<?php

namespace App\Http\Controllers;

use App\Enums\Achievement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AchievementController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('achievements.show', $request->user());
    }

    public function show(User $user): View
    {
        $records = $user->achievements()
            ->latest('awarded_at')
            ->get()
            ->keyBy(fn ($record) => $record->key->value);
        $catalog = collect(Achievement::cases())->map(
            fn (Achievement $achievement): array => [
                'achievement' => $achievement,
                'record' => $records->get($achievement->value),
            ],
        );

        return view('achievements.index', [
            'profile' => $user,
            'earned' => $catalog->filter(fn (array $item): bool => $item['record'] !== null)
                ->sortByDesc(fn (array $item) => $item['record']->awarded_at)
                ->values(),
            'locked' => $catalog->filter(fn (array $item): bool => $item['record'] === null)->values(),
        ]);
    }
}
