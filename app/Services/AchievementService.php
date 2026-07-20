<?php

namespace App\Services;

use App\Enums\Achievement;
use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\GameReview;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Collection;

class AchievementService
{
    public function __construct(private readonly SocialNotificationService $notifications) {}

    /** @return Collection<int, UserAchievement> */
    public function evaluate(User $user): Collection
    {
        $counts = $this->counts($user);
        $awards = collect();

        foreach ($this->eligibleAchievements($user, $counts) as $achievement) {
            $award = $user->achievements()->firstOrCreate(
                ['key' => $achievement->value],
                ['awarded_at' => now()],
            );

            if (! $award->wasRecentlyCreated) {
                continue;
            }

            $awards->push($award);
            $this->notifyAboutAward($user, $achievement);
        }

        return $awards;
    }

    /** @return array<string, int> */
    private function counts(User $user): array
    {
        $games = Game::query()->whereHas(
            'gameList',
            fn ($query) => $query->where('user_id', $user->getKey()),
        );
        $reviews = GameReview::query()->where('user_id', $user->getKey());

        return [
            'games' => (clone $games)->count(),
            'installed' => (clone $games)->where('status', GameStatus::Installed->value)->count(),
            'dropped' => (clone $games)->where('status', GameStatus::Dropped->value)->count(),
            'completed' => (clone $games)->where('status', GameStatus::Completed->value)->count(),
            'wishlist' => (clone $games)->where('status', GameStatus::WantToPlay->value)->count(),
            'playing' => (clone $games)->where('status', GameStatus::Playing->value)->count(),
            'opinions' => (clone $reviews)->whereNotNull('body')->count(),
            'ratings' => (clone $reviews)->whereNotNull('rating')->count(),
            'friends' => $user->friends()->count(),
        ];
    }

    /** @param array<string, int> $counts
     * @return array<int, Achievement>
     */
    private function eligibleAchievements(User $user, array $counts): array
    {
        $achievements = [];

        $this->addWhen($achievements, $counts['games'] >= 1, Achievement::Games1);
        $this->addWhen($achievements, filled($user->avatar_path), Achievement::Avatar1);
        $this->addWhen($achievements, $counts['installed'] >= 1, Achievement::Installed1);
        $this->addWhen($achievements, $counts['dropped'] >= 1, Achievement::Drops1);
        $this->addWhen($achievements, $counts['completed'] >= 1, Achievement::Completions1);
        $this->addMilestones($achievements, $counts['dropped'], [
            3 => Achievement::Drops3,
            5 => Achievement::Drops5,
            10 => Achievement::Drops10,
            100 => Achievement::Drops100,
            1000 => Achievement::Drops1000,
        ]);
        $this->addMilestones($achievements, $counts['completed'], [
            3 => Achievement::Completions3,
            5 => Achievement::Completions5,
            10 => Achievement::Completions10,
            100 => Achievement::Completions100,
            1000 => Achievement::Completions1000,
        ]);
        $this->addMilestones($achievements, $counts['wishlist'], [
            100 => Achievement::Wishlist100,
            1000 => Achievement::Wishlist1000,
        ]);
        $this->addMilestones($achievements, $counts['playing'], [
            3 => Achievement::Playing3,
            5 => Achievement::Playing5,
            10 => Achievement::Playing10,
        ]);
        $this->addWhen($achievements, $counts['opinions'] >= 1, Achievement::Opinions1);
        $this->addWhen($achievements, $counts['ratings'] >= 1, Achievement::Ratings1);
        $this->addMilestones($achievements, $counts['opinions'], [
            3 => Achievement::Opinions3,
            5 => Achievement::Opinions5,
            10 => Achievement::Opinions10,
            100 => Achievement::Opinions100,
        ]);
        $this->addMilestones($achievements, $counts['ratings'], [
            5 => Achievement::Ratings5,
            10 => Achievement::Ratings10,
            100 => Achievement::Ratings100,
            1000 => Achievement::Ratings1000,
        ]);
        $this->addWhen($achievements, filled($user->email), Achievement::GoldStatus);
        $this->addWhen($achievements, $counts['friends'] >= 1, Achievement::Friends1);
        $this->addMilestones($achievements, $counts['friends'], [
            3 => Achievement::Friends3,
            5 => Achievement::Friends5,
            10 => Achievement::Friends10,
            100 => Achievement::Friends100,
        ]);

        return $achievements;
    }

    /** @param array<int, Achievement> $achievements */
    private function addWhen(array &$achievements, bool $condition, Achievement $achievement): void
    {
        if ($condition) {
            $achievements[] = $achievement;
        }
    }

    /**
     * @param  array<int, Achievement>  $achievements
     * @param  array<int, Achievement>  $milestones
     */
    private function addMilestones(array &$achievements, int $count, array $milestones): void
    {
        foreach ($milestones as $threshold => $achievement) {
            $this->addWhen($achievements, $count >= $threshold, $achievement);
        }
    }

    private function notifyAboutAward(User $user, Achievement $achievement): void
    {
        $this->notifications->notifyUser(
            $user,
            'achievement_unlocked',
            "Поздравляем! Достижение «{$achievement->title()}» разблокировано.",
            route('achievements.show', $user, false),
            $achievement->icon(),
        );
        $this->notifications->notifyFollowers(
            $user,
            'friend_achievement_unlocked',
            "@{$user->login} получает достижение «{$achievement->title()}».",
            route('profiles.show', $user->login, false),
            $achievement->icon(),
        );
    }
}
