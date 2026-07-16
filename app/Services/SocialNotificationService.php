<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ActivityNotification;

class SocialNotificationService
{
    public function notifyUser(
        User $user,
        string $event,
        string $message,
        string $url,
        string $icon,
    ): void {
        $user->notify(new ActivityNotification($event, $message, $url, $icon));
    }

    public function notifyFollowers(
        User $actor,
        string $event,
        string $message,
        string $url,
        string $icon,
    ): void {
        $actor->followers()->get()->each(
            fn (User $follower) => $this->notifyUser($follower, $event, $message, $url, $icon),
        );
    }
}
