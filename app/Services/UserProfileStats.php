<?php

namespace App\Services;

use App\Enums\GameStatus;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserProfileStats
{
    /** @return array{friends: int, public_lists: int, public_games: int, statuses: array<string, int>} */
    public function forUser(User $user): array
    {
        return $this->forUsers(collect([$user]))[$user->getKey()];
    }

    /**
     * @param  Collection<int, User>  $users
     * @return array<int, array{friends: int, public_lists: int, public_games: int, statuses: array<string, int>}>
     */
    public function forUsers(Collection $users): array
    {
        $ids = $users->pluck('id')->map(fn ($id): int => (int) $id)->all();
        if ($ids === []) {
            return [];
        }

        $friendCounts = DB::table('friendships')
            ->whereIn('user_id', $ids)
            ->selectRaw('user_id, count(*) as aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');
        $listCounts = DB::table('game_lists')
            ->whereIn('user_id', $ids)
            ->where('is_public', true)
            ->selectRaw('user_id, count(*) as aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id');
        $gameCounts = DB::table('games')
            ->join('game_lists', 'game_lists.id', '=', 'games.game_list_id')
            ->whereIn('game_lists.user_id', $ids)
            ->where('game_lists.is_public', true)
            ->selectRaw('game_lists.user_id, count(*) as aggregate')
            ->groupBy('game_lists.user_id')
            ->pluck('aggregate', 'game_lists.user_id');
        $statusRows = DB::table('games')
            ->join('game_lists', 'game_lists.id', '=', 'games.game_list_id')
            ->whereIn('game_lists.user_id', $ids)
            ->where('game_lists.is_public', true)
            ->selectRaw('game_lists.user_id, games.status, count(*) as aggregate')
            ->groupBy('game_lists.user_id', 'games.status')
            ->get();

        $statusesByUser = $statusRows->groupBy('user_id')->map(
            fn (Collection $rows): array => $rows->pluck('aggregate', 'status')
                ->map(fn ($count): int => (int) $count)
                ->all(),
        );

        return $users->mapWithKeys(function (User $user) use ($friendCounts, $listCounts, $gameCounts, $statusesByUser): array {
            $statuses = array_fill_keys(array_column(GameStatus::cases(), 'value'), 0);

            return [$user->getKey() => [
                'friends' => (int) ($friendCounts[$user->getKey()] ?? 0),
                'public_lists' => (int) ($listCounts[$user->getKey()] ?? 0),
                'public_games' => (int) ($gameCounts[$user->getKey()] ?? 0),
                'statuses' => array_replace($statuses, $statusesByUser[$user->getKey()] ?? []),
            ]];
        })->all();
    }
}
