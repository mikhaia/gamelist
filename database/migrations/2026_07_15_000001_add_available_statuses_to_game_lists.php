<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_lists', function (Blueprint $table) {
            $table->json('available_statuses')->nullable()->after('default_platform');
        });
    }

    public function down(): void
    {
        Schema::table('game_lists', function (Blueprint $table) {
            $table->dropColumn('available_statuses');
        });
    }
};
