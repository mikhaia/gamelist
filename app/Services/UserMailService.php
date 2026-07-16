<?php

namespace App\Services;

use App\Mail\FriendAddedMail;
use App\Mail\InactivePlayerMail;
use App\Mail\PasswordChangedMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\Game;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class UserMailService
{
    public function sendFriendAdded(User $recipient, User $friend): bool
    {
        if (! $recipient->email) {
            return false;
        }

        return $this->send($recipient, new FriendAddedMail($recipient, $friend), 'friend_added');
    }

    /** @param Collection<int, Game> $playingGames */
    public function sendInactiveReminder(User $recipient, Collection $playingGames): bool
    {
        if (! $recipient->email) {
            return false;
        }

        return $this->send($recipient, new InactivePlayerMail($recipient, $playingGames), 'inactive_reminder');
    }

    public function sendPasswordResetOtp(User $recipient, string $code): bool
    {
        return $this->send($recipient, new PasswordResetOtpMail($recipient, $code), 'password_reset_otp');
    }

    public function sendPasswordChanged(User $recipient): bool
    {
        return $this->send($recipient, new PasswordChangedMail($recipient), 'password_changed');
    }

    private function send(User $recipient, Mailable $mailable, string $event): bool
    {
        try {
            Mail::to($recipient->email)->send($mailable);

            return true;
        } catch (Throwable $exception) {
            Log::error('User email delivery failed', [
                'event' => $event,
                'user_id' => $recipient->getKey(),
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
