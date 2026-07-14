<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar_path')->nullable()->after('password');
        });

        Schema::table('game_lists', function (Blueprint $table) {
            $table->string('cover_path')->nullable()->after('description');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->date('started_at')->nullable()->after('platform');
            $table->date('completed_at')->nullable()->after('started_at');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'completed_at']);
        });

        Schema::table('game_lists', function (Blueprint $table) {
            $table->dropColumn('cover_path');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatar_path');
        });
    }
};
