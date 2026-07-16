<?php

namespace App\Console\Commands;

use App\Enums\GameStatus;
use App\Models\User;
use App\Services\UserMailService;
use Illuminate\Console\Command;

class SendInactiveReminders extends Command
{
    protected $signature = 'gamelist:send-inactive-reminders';

    protected $description = 'Send a friendly email to users inactive for at least one month';

    public function handle(UserMailService $mail): int
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
            ->chunkById(100, function ($users) use ($mail, &$sent): void {
                foreach ($users as $user) {
                    $playingGames = $user->gameLists->flatMap->games;

                    if ($mail->sendInactiveReminder($user, $playingGames)) {
                        $user->forceFill(['inactive_reminder_sent_at' => now()])->saveQuietly();
                        $sent++;
                    }
                }
            });

        $this->info("Inactive reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
