<?php

namespace App\Console\Commands;

use App\Services\InactiveReminderService;
use Illuminate\Console\Command;

class SendInactiveReminders extends Command
{
    protected $signature = 'gamelist:send-inactive-reminders';

    protected $description = 'Send a friendly email to users inactive for at least one month';

    public function handle(InactiveReminderService $reminders): int
    {
        $sent = $reminders->sendDueReminders();

        $this->info("Inactive reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
