<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_games', function (Blueprint $table): void {
            $table->unsignedBigInteger('hltb_id')->nullable()->change();
            $table->json('genre_slugs')->nullable()->after('genres');
            $table->json('platform_ids')->nullable()->after('platforms');
            $table->unsignedInteger('rawg_added')->nullable()->after('platform_ids');
            $table->dropIndex(['rawg_id']);
            $table->unique('rawg_id');
        });

        DB::table('catalog_games')
            ->whereNotNull('rawg_id')
            ->update(['rawg_synced_at' => null]);
    }

    public function down(): void
    {
        DB::table('catalog_games')->whereNull('hltb_id')->delete();

        Schema::table('catalog_games', function (Blueprint $table): void {
            $table->dropUnique(['rawg_id']);
            $table->index('rawg_id');
            $table->dropColumn(['genre_slugs', 'platform_ids', 'rawg_added']);
            $table->unsignedBigInteger('hltb_id')->nullable(false)->change();
        });
    }
};
