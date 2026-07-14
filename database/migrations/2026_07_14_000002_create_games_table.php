<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_list_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->string('normalized_title', 255);
            $table->string('status', 32)->default('want_to_play');
            $table->string('platform', 32)->default('nintendo_switch');
            $table->unsignedBigInteger('hltb_id')->nullable();
            $table->string('cover_path')->nullable();
            $table->text('source_cover_url')->nullable();
            $table->unsignedInteger('main_story_minutes')->nullable();
            $table->unsignedInteger('main_extra_minutes')->nullable();
            $table->unsignedInteger('completionist_minutes')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['game_list_id', 'normalized_title']);
            $table->index(['game_list_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
