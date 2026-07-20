<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KEY_MAP = [
        'first_game_added' => 'games_1',
        'first_game_installed' => 'installed_1',
        'first_game_dropped' => 'drops_1',
        'first_game_completed' => 'completions_1',
        'first_opinion' => 'opinions_1',
        'first_rating' => 'ratings_1',
        'first_friend' => 'friends_1',
    ];

    public function up(): void
    {
        foreach (self::KEY_MAP as $from => $to) {
            DB::table('user_achievements')->where('key', $from)->update(['key' => $to]);
        }
    }

    public function down(): void
    {
        foreach (array_flip(self::KEY_MAP) as $from => $to) {
            DB::table('user_achievements')->where('key', $from)->update(['key' => $to]);
        }
    }
};
