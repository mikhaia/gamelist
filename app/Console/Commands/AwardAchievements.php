<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AchievementService;
use Illuminate\Console\Command;

class AwardAchievements extends Command
{
    protected $signature = 'gamelist:award-achievements {--user= : ID пользователя для проверки}';

    protected $description = 'Award achievements that match each user’s current progress';

    public function handle(AchievementService $achievements): int
    {
        $awards = 0;
        $users = User::query()->when(
            $this->option('user'),
            fn ($query, $userId) => $query->whereKey($userId),
        );

        $users->chunkById(100, function ($users) use ($achievements, &$awards): void {
            foreach ($users as $user) {
                $awards += $achievements->evaluate($user)->count();
            }
        });

        $this->info("Achievements awarded: {$awards}");

        return self::SUCCESS;
    }
}
