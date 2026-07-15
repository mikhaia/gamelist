<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_games', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hltb_id')->unique();
            $table->string('title');
            $table->string('normalized_title')->index();
            $table->text('cover_url')->nullable();
            $table->unsignedInteger('main_story_minutes')->nullable();
            $table->unsignedInteger('completionist_minutes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_games');
    }
};
