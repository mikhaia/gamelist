<?php

namespace Tests\Feature;

use App\Mail\PasswordChangedMail;
use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_reset_password_with_a_six_digit_email_code(): void
    {
        Mail::fake();
        $this->withoutDefer();
        $user = User::factory()->create([
            'email' => 'player@example.com',
            'password' => 'old-password',
            'remember_token' => 'old-remember-token',
        ]);

        $this->post(route('password.email'), [
            'email' => ' PLAYER@EXAMPLE.COM ',
        ])->assertRedirect(route('password.otp'))
            ->assertSessionHas('password_reset_email', 'player@example.com');

        $code = null;
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$code, $user): bool {
            $code = $mail->code;

            return $mail->recipient->is($user)
                && preg_match('/^\d{6}$/', $mail->code) === 1
                && str_contains($mail->render(), $mail->code);
        });

        $this->assertIsString($code);
        $otp = PasswordResetOtp::query()->firstOrFail();
        $this->assertNotSame($code, $otp->code_hash);
        $this->assertTrue(Hash::check($code, $otp->code_hash));
        DB::table('sessions')->insert([
            'id' => 'old-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->withSession(['password_reset_email' => 'player@example.com'])
            ->post(route('password.update'), [
                'code' => $code,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        $this->assertGuest();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNotSame('old-remember-token', $user->fresh()->remember_token);
        $this->assertDatabaseCount('password_reset_otps', 0);
        $this->assertDatabaseMissing('sessions', ['id' => 'old-session']);
        Mail::assertSent(PasswordChangedMail::class, fn (PasswordChangedMail $mail): bool => $mail->recipient->is($user)
            && str_contains($mail->render(), '@'.$user->login));
    }

    public function test_password_reset_request_does_not_reveal_an_unknown_email(): void
    {
        Mail::fake();
        $this->withoutDefer();

        $response = $this->post(route('password.email'), ['email' => 'unknown@example.com']);

        $response->assertRedirect(route('password.otp'))
            ->assertSessionHas('success', __('app.messages.password_reset_code_sent'));
        $this->assertDatabaseCount('password_reset_otps', 0);
        Mail::assertNothingSent();
    }

    public function test_expired_code_cannot_be_used(): void
    {
        Mail::fake();
        $this->withoutDefer();
        $this->travelTo(Carbon::parse('2026-07-16 12:00:00'));
        User::factory()->create(['email' => 'player@example.com']);

        $code = $this->requestCode('player@example.com');
        $this->travel(11)->minutes();

        $this->withSession(['password_reset_email' => 'player@example.com'])
            ->post(route('password.update'), [
                'code' => $code,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasErrors('code');

        $this->assertDatabaseCount('password_reset_otps', 0);
    }

    public function test_code_is_rejected_after_five_wrong_attempts(): void
    {
        Mail::fake();
        $this->withoutDefer();
        $user = User::factory()->create([
            'email' => 'player@example.com',
            'password' => 'old-password',
        ]);
        $code = $this->requestCode('player@example.com');
        $wrongCode = $code === '999998' ? '999997' : '999998';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withSession(['password_reset_email' => 'player@example.com'])
                ->post(route('password.update'), [
                    'code' => $wrongCode,
                    'password' => 'new-password',
                    'password_confirmation' => 'new-password',
                ])->assertSessionHasErrors('code');
        }

        $this->assertSame(5, PasswordResetOtp::query()->firstOrFail()->attempts);

        $this->withSession(['password_reset_email' => 'player@example.com'])
            ->post(route('password.update'), [
                'code' => $code,
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])->assertSessionHasErrors('code');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
        $this->assertDatabaseCount('password_reset_otps', 0);
    }

    public function test_requesting_codes_is_rate_limited_per_email(): void
    {
        Mail::fake();
        $this->withoutDefer();
        User::factory()->create(['email' => 'player@example.com']);

        for ($request = 0; $request < 3; $request++) {
            $this->post(route('password.email'), ['email' => 'player@example.com'])
                ->assertRedirect(route('password.otp'));
        }

        $this->post(route('password.email'), ['email' => 'player@example.com'])
            ->assertSessionHasErrors('email');
        Mail::assertSentCount(3);
    }

    public function test_reset_code_is_single_use(): void
    {
        Mail::fake();
        $this->withoutDefer();
        User::factory()->create(['email' => 'player@example.com']);
        $code = $this->requestCode('player@example.com');

        $this->withSession(['password_reset_email' => 'player@example.com'])
            ->post(route('password.update'), [
                'code' => $code,
                'password' => 'first-password',
                'password_confirmation' => 'first-password',
            ])->assertRedirect(route('login'));

        $this->withSession(['password_reset_email' => 'player@example.com'])
            ->post(route('password.update'), [
                'code' => $code,
                'password' => 'second-password',
                'password_confirmation' => 'second-password',
            ])->assertSessionHasErrors('code');
    }

    public function test_settings_explain_email_benefits_and_login_links_to_recovery(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Gold-статус')
            ->assertSee('восстановить доступ');
        $this->post(route('logout'));
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Забыли пароль?')
            ->assertSee(route('password.request'), false);
    }

    private function requestCode(string $email): string
    {
        $code = null;

        $this->post(route('password.email'), ['email' => $email]);
        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        return $code ?? throw new \RuntimeException('OTP email was not sent.');
    }
}
