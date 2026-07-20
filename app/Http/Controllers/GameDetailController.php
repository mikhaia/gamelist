<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameComment;
use App\Services\GameAccess;
use App\Services\ReviewMarkdown;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class GameDetailController extends Controller
{
    public function __construct(
        private readonly GameAccess $access,
        private readonly ReviewMarkdown $markdown,
    ) {}

    public function show(Request $request, Game $game): View
    {
        $this->access->authorizeView($request->user(), $game);
        $game->load(['catalogGame', 'gameList.user', 'screenshots']);

        $viewer = $request->user();
        $isOwner = $this->access->isOwner($viewer, $game);
        $ownerReview = $game->catalogGame?->reviews()->where('user_id', $game->gameList->user_id)->first();
        $comments = $game->comments()->with('user')->get()->filter(
            fn (GameComment $comment): bool => $comment->hidden_at === null
                || $isOwner
                || $comment->user_id === $viewer?->id,
        );

        return view('games.entry', [
            'game' => $game,
            'owner' => $game->gameList->user,
            'isOwner' => $isOwner,
            'ownerReview' => $ownerReview,
            'commentTree' => $this->commentTree($comments),
            'renderedNotes' => $this->markdown->render($game->notes),
            'renderedOpinion' => $this->markdown->render($ownerReview?->body),
        ]);
    }

    /** @return Collection<int, array{comment: GameComment, children: Collection}> */
    private function commentTree(Collection $comments, ?int $parentId = null): Collection
    {
        return $comments
            ->filter(fn (GameComment $comment): bool => $comment->parent_id === $parentId)
            ->map(fn (GameComment $comment): array => [
                'comment' => $comment,
                'children' => $this->commentTree($comments, $comment->id),
            ])
            ->values();
    }
}
