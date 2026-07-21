<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropUnique('games_game_list_id_normalized_title_unique');
            $table->index(['game_list_id', 'normalized_title']);
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropIndex(['game_list_id', 'normalized_title']);
            $table->unique(['game_list_id', 'normalized_title']);
        });
    }
};
