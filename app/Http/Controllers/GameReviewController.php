<?php

namespace App\Http\Controllers;

use App\Models\CatalogGame;
use App\Services\AchievementService;
use App\Services\ReviewMarkdown;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GameReviewController extends Controller
{
    public function __construct(
        private readonly AchievementService $achievements,
        private readonly ReviewMarkdown $markdown,
    ) {}

    public function update(Request $request, CatalogGame $catalogGame): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,10'],
            'body' => ['nullable', 'string', 'max:10000'],
        ]);
        $body = trim((string) ($validated['body'] ?? '')) ?: null;
        $rating = isset($validated['rating']) ? (int) $validated['rating'] : null;

        if ($rating === null && $body === null) {
            throw ValidationException::withMessages([
                'body' => __('app.errors.review_required'),
            ]);
        }

        $catalogGame->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['rating' => $rating, 'body' => $body],
        );
        $this->achievements->evaluate($request->user());

        return redirect()->route('games.show', $catalogGame)
            ->with('success', __('app.messages.review_saved'));
    }

    public function destroy(Request $request, CatalogGame $catalogGame): RedirectResponse
    {
        $catalogGame->reviews()->where('user_id', $request->user()->id)->delete();

        return redirect()->route('games.show', $catalogGame)
            ->with('success', __('app.messages.review_deleted'));
    }

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:10000'],
        ]);

        return response()->json([
            'html' => $this->markdown->render($validated['body'] ?? null),
        ]);
    }
}
