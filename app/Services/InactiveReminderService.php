<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\User;

class InactiveReminderService
{
    public function __construct(private readonly UserMailService $mail) {}

    public function sendDueReminders(): int
    {
        $sent = 0;

        User::query()
            ->whereNotNull('email')
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<=', now()->subMonth())
            ->where(function ($query): void {
                $query->whereNull('inactive_reminder_sent_at')
                    ->orWhereColumn('inactive_reminder_sent_at', '<', 'last_seen_at');
            })
            ->with(['gameLists.games' => fn ($query) => $query->where('status', GameStatus::Playing->value)])
            ->chunkById(100, function ($users) use (&$sent): void {
                foreach ($users as $user) {
                    $playingGames = $user->gameLists->flatMap->games;

                    if ($this->mail->sendInactiveReminder($user, $playingGames)) {
                        $user->forceFill(['inactive_reminder_sent_at' => now()])->saveQuietly();
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
