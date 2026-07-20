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

        if (! $request->has('rating') && ! $request->has('body')) {
            throw ValidationException::withMessages([
                'body' => __('app.errors.review_required'),
            ]);
        }

        $this->saveReview(
            $request,
            $catalogGame,
            $request->has('rating'),
            $request->filled('rating') ? (int) $validated['rating'] : null,
            $request->has('body'),
            trim((string) ($validated['body'] ?? '')) ?: null,
        );

        return redirect()->route('games.show', $catalogGame)
            ->with('success', __('app.messages.review_saved'));
    }

    public function updateRating(Request $request, CatalogGame $catalogGame): RedirectResponse
    {
        $validated = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,10'],
        ]);

        $this->saveReview(
            $request,
            $catalogGame,
            true,
            $request->filled('rating') ? (int) $validated['rating'] : null,
            false,
            null,
        );

        return back()->with('success', 'Оценка сохранена.');
    }

    public function updateOpinion(Request $request, CatalogGame $catalogGame): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:10000'],
        ]);

        $this->saveReview(
            $request,
            $catalogGame,
            false,
            null,
            true,
            trim((string) ($validated['body'] ?? '')) ?: null,
        );

        return back()->with('success', 'Мнение сохранено.');
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

    private function saveReview(
        Request $request,
        CatalogGame $catalogGame,
        bool $updatesRating,
        ?int $rating,
        bool $updatesBody,
        ?string $body,
    ): void {
        $review = $catalogGame->reviews()->where('user_id', $request->user()->id)->first();
        $nextRating = $updatesRating ? $rating : $review?->rating;
        $nextBody = $updatesBody ? $body : $review?->body;

        if ($nextRating === null && $nextBody === null) {
            $review?->delete();

            return;
        }

        $catalogGame->reviews()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['rating' => $nextRating, 'body' => $nextBody],
        );
        $this->achievements->evaluate($request->user());
    }
}
