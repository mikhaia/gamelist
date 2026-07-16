<?php

namespace App\Http\Controllers;

use App\Models\Friendship;
use App\Models\User;
use App\Services\SocialNotificationService;
use App\Services\UserMailService;
use App\Services\UserProfileStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FriendController extends Controller
{
    public function __construct(
        private readonly SocialNotificationService $notifications,
        private readonly UserProfileStats $profileStats,
        private readonly UserMailService $mail,
    ) {}

    public function index(Request $request): View
    {
        $friends = $request->user()->friends()
            ->orderByDesc('friendships.created_at')
            ->get();
        $friendIds = $friends->modelKeys();
        $incoming = $request->user()->followers()
            ->when($friendIds !== [], fn ($query) => $query->whereNotIn('users.id', $friendIds))
            ->orderByDesc('friendships.created_at')
            ->get();
        $people = $friends->concat($incoming)->unique('id')->values();

        return view('friends.index', [
            'friends' => $friends,
            'incoming' => $incoming,
            'statsByUser' => $this->profileStats->forUsers($people),
        ]);
    }

    public function store(Request $request, User $friend): RedirectResponse
    {
        abort_if($request->user()->is($friend), 422);

        $friendship = Friendship::query()->firstOrCreate([
            'user_id' => $request->user()->getKey(),
            'friend_id' => $friend->getKey(),
        ]);

        if ($friendship->wasRecentlyCreated) {
            $actor = $request->user();
            $this->notifications->notifyUser(
                $friend,
                'friend_added',
                "@{$actor->login} добавил вас в друзья.",
                route('profiles.show', $actor->login, false),
                'person_add',
            );
            $this->mail->sendFriendAdded($friend, $actor);
        }

        return back()->with('success', __('app.messages.friend_added'));
    }

    public function destroy(Request $request, User $friend): RedirectResponse
    {
        Friendship::query()
            ->where('user_id', $request->user()->getKey())
            ->where('friend_id', $friend->getKey())
            ->delete();

        return back()->with('success', __('app.messages.friend_removed'));
    }
}
