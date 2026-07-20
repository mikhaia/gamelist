<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('games')
            ->join('game_lists', 'game_lists.id', '=', 'games.game_list_id')
            ->whereNotNull('games.catalog_game_id')
            ->where(function ($query): void {
                $query->whereNotNull('games.owner_rating')
                    ->orWhereNotNull('games.owner_opinion');
            })
            ->select([
                'games.id as game_id',
                'games.catalog_game_id',
                'games.owner_rating',
                'games.owner_opinion',
                'game_lists.user_id',
            ])
            ->orderBy('games.id')
            ->chunkById(100, function ($games): void {
                foreach ($games as $game) {
                    $review = DB::table('game_reviews')
                        ->where('user_id', $game->user_id)
                        ->where('catalog_game_id', $game->catalog_game_id)
                        ->first();

                    $rating = $review?->rating ?? $game->owner_rating;
                    $body = $review?->body ?? $game->owner_opinion;

                    if ($review) {
                        DB::table('game_reviews')->where('id', $review->id)->update([
                            'rating' => $rating,
                            'body' => $body,
                            'updated_at' => now(),
                        ]);

                        continue;
                    }

                    DB::table('game_reviews')->insert([
                        'user_id' => $game->user_id,
                        'catalog_game_id' => $game->catalog_game_id,
                        'rating' => $rating,
                        'body' => $body,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }, 'games.id', 'game_id');
    }

    public function down(): void
    {
        // Personal values are intentionally kept in catalog reviews after the migration.
    }
};
