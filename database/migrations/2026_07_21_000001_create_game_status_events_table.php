<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_status_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32);
            $table->timestamp('changed_at');

            $table->index(['game_id', 'changed_at']);
        });

        DB::table('games')
            ->select(['id', 'status', 'created_at', 'updated_at'])
            ->orderBy('id')
            ->chunkById(500, function ($games): void {
                DB::table('game_status_events')->insert(
                    $games->map(fn ($game): array => [
                        'game_id' => $game->id,
                        'status' => $game->status,
                        'changed_at' => $game->updated_at ?? $game->created_at ?? now(),
                    ])->all(),
                );
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_status_events');
    }
};
