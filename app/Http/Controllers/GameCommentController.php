<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameComment;
use App\Services\GameAccess;
use App\Services\SocialNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GameCommentController extends Controller
{
    public function __construct(
        private readonly GameAccess $access,
        private readonly SocialNotificationService $notifications,
    ) {}

    public function store(Request $request, Game $game): RedirectResponse
    {
        $actor = $request->user();
        $this->access->authorizeView($actor, $game);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
            'parent_id' => ['nullable', 'integer'],
        ]);
        $parent = null;

        if (isset($validated['parent_id'])) {
            $parent = $game->comments()->with('user')->findOrFail($validated['parent_id']);
            $canReplyToHiddenComment = $this->access->isOwner($actor, $game) || $parent->user_id === $actor->id;
            abort_if($parent->hidden_at !== null && ! $canReplyToHiddenComment, 403);
        }

        $comment = $game->comments()->create([
            'user_id' => $actor->id,
            'parent_id' => $parent?->id,
            'body' => trim($validated['body']),
        ]);
        $owner = $game->gameList()->with('user')->firstOrFail()->user;
        $url = route('games.view', $game, false).'#comment-'.$comment->id;
        $notifiedUserIds = [];

        if ($owner->id !== $actor->id) {
            $this->notifications->notifyUser(
                $owner,
                'game_comment',
                "@{$actor->login} оставил комментарий к вашей записи «{$game->title}».",
                $url,
                'notifications',
                ['game_id' => $game->id, 'game_comment_id' => $comment->id],
            );
            $notifiedUserIds[] = $owner->id;
        }

        if ($parent && $parent->user_id !== $actor->id && ! in_array($parent->user_id, $notifiedUserIds, true)) {
            $this->notifications->notifyUser(
                $parent->user,
                'game_comment_reply',
                "@{$actor->login} ответил на ваш комментарий к записи «{$game->title}».",
                $url,
                'notifications',
                ['game_id' => $game->id, 'game_comment_id' => $comment->id],
            );
        }

        return redirect($url)->with('success', 'Комментарий опубликован.');
    }

    public function toggleVisibility(Request $request, Game $game, GameComment $comment): RedirectResponse
    {
        $this->access->authorizeOwner($request->user(), $game);
        abort_unless($comment->game_id === $game->id, 404);

        $comment->update(['hidden_at' => $comment->hidden_at ? null : now()]);

        return back()->with('success', $comment->hidden_at ? 'Комментарий скрыт.' : 'Комментарий снова виден.');
    }
}
