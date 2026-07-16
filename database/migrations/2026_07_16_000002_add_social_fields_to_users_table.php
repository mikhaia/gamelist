<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('profile_cover_path')->nullable()->after('avatar_path');
            $table->timestamp('last_seen_at')->nullable()->after('remember_token');
            $table->timestamp('inactive_reminder_sent_at')->nullable()->after('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['profile_cover_path', 'last_seen_at', 'inactive_reminder_sent_at']);
        });
    }
};
