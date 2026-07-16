<?php

namespace App\Services;

use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use function Illuminate\Support\defer;

class PasswordResetOtpService
{
    public const EXPIRES_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public function __construct(private readonly UserMailService $mail) {}

    public function issue(string $email): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = Hash::make($code);
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return;
        }

        PasswordResetOtp::query()->updateOrCreate(
            ['email' => $email],
            [
                'code_hash' => $codeHash,
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::EXPIRES_MINUTES),
            ],
        );

        defer(fn () => $this->mail->sendPasswordResetOtp($user, $code));
    }

    public function consume(string $email, string $code): ?User
    {
        return DB::transaction(function () use ($email, $code): ?User {
            $otp = PasswordResetOtp::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (! $otp || $otp->expires_at->isPast()) {
                $otp?->delete();

                return null;
            }

            if ($otp->attempts >= self::MAX_ATTEMPTS) {
                $otp->delete();

                return null;
            }

            if (! Hash::check($code, $otp->code_hash)) {
                $otp->increment('attempts');

                return null;
            }

            $user = User::query()->where('email', $email)->first();
            if (! $user) {
                $otp->delete();

                return null;
            }

            $otp->delete();

            return $user;
        });
    }
}
