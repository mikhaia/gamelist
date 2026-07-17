<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_games', function (Blueprint $table): void {
            $table->unsignedBigInteger('rawg_id')->nullable()->after('hltb_id')->index();
            $table->string('rawg_slug')->nullable()->after('rawg_id');
            $table->json('screenshots')->nullable()->after('cover_url');
            $table->json('genres')->nullable()->after('screenshots');
            $table->json('platforms')->nullable()->after('genres');
            $table->string('steam_id', 32)->nullable()->after('platforms');
            $table->timestamp('rawg_synced_at')->nullable()->after('steam_id');
        });
    }

    public function down(): void
    {
        Schema::table('catalog_games', function (Blueprint $table): void {
            $table->dropIndex(['rawg_id']);
            $table->dropColumn([
                'rawg_id',
                'rawg_slug',
                'screenshots',
                'genres',
                'platforms',
                'steam_id',
                'rawg_synced_at',
            ]);
        });
    }
};
