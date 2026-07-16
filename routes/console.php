<?php

use App\Models\PasswordResetOtp;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('gamelist:send-inactive-reminders')
    ->dailyAt('09:00')
    ->withoutOverlapping();

Schedule::call(fn () => PasswordResetOtp::query()
    ->where('expires_at', '<', now())
    ->delete())
    ->daily()
    ->name('password-reset-otps:prune')
    ->withoutOverlapping();
