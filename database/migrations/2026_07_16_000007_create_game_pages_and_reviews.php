<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->foreignId('catalog_game_id')
                ->nullable()
                ->after('game_list_id')
                ->constrained('catalog_games')
                ->nullOnDelete();
        });

        $this->linkExistingGames();

        Schema::create('game_reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_game_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating')->nullable();
            $table->text('body')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'catalog_game_id']);
            $table->index(['catalog_game_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_reviews');

        Schema::table('games', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('catalog_game_id');
        });
    }

    private function linkExistingGames(): void
    {
        DB::table('games')->whereNotNull('hltb_id')->orderBy('id')->chunkById(200, function ($games): void {
            foreach ($games as $game) {
                $catalogGameId = DB::table('catalog_games')
                    ->where('hltb_id', $game->hltb_id)
                    ->value('id');

                if ($catalogGameId) {
                    DB::table('games')->where('id', $game->id)->update([
                        'catalog_game_id' => $catalogGameId,
                    ]);
                }
            }
        });
    }
};
