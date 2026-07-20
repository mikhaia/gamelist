<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->unsignedTinyInteger('owner_rating')->nullable()->after('notes');
            $table->text('owner_opinion')->nullable()->after('owner_rating');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropColumn(['owner_rating', 'owner_opinion']);
        });
    }
};
