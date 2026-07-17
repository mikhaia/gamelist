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
            $table->string('age_rating')->nullable()->after('genres');
        });

        DB::table('catalog_games')
            ->whereNotNull('rawg_id')
            ->update(['rawg_synced_at' => null]);
    }

    public function down(): void
    {
        Schema::table('catalog_games', function (Blueprint $table): void {
            $table->dropColumn('age_rating');
        });
    }
};
