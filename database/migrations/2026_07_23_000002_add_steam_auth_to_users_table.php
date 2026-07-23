<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('password')->nullable()->change();
            $table->string('steam_id', 20)->nullable()->unique()->after('is_admin');
            $table->timestamp('steam_connected_at')->nullable()->after('steam_id');
        });
    }

    public function down(): void
    {
        DB::table('users')->whereNull('password')->update([
            'password' => Hash::make(Str::random(64)),
        ]);

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['steam_id']);
            $table->dropColumn(['steam_id', 'steam_connected_at']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
