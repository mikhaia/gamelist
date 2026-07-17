<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('catalog_games', 'background')) {
            Schema::table('catalog_games', function (Blueprint $table): void {
                $table->dropColumn('background');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('catalog_games', 'background')) {
            Schema::table('catalog_games', function (Blueprint $table): void {
                $table->text('background')->nullable()->after('cover_url');
            });
        }
    }
};
