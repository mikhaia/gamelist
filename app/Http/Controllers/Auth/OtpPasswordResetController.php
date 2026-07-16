<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\PasswordResetOtpService;
use App\Services\UserMailService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

use function Illuminate\Support\defer;

class OtpPasswordResetController extends Controller
{
    public function __construct(
        private readonly PasswordResetOtpService $otps,
        private readonly UserMailService $mail,
    ) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function send(Request $request): RedirectResponse
    {
        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => strtolower(trim($email))]);
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);
        $rateLimitKey = $this->rateLimitKey($validated['email']);

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            throw ValidationException::withMessages([
                'email' => __('app.errors.password_reset_throttled', [
                    'seconds' => RateLimiter::availableIn($rateLimitKey),
                ]),
            ]);
        }

        RateLimiter::hit($rateLimitKey, 60);
        $this->otps->issue($validated['email']);
        $request->session()->put('password_reset_email', $validated['email']);

        return redirect()->route('password.otp')
            ->with('success', __('app.messages.password_reset_code_sent'));
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password-otp', [
            'email' => $request->session()->get('password_reset_email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');
        if (! is_string($email) || $email === '') {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $user = $this->otps->consume($email, $validated['code']);

        if (! $user) {
            throw ValidationException::withMessages([
                'code' => __('app.errors.password_reset_code_invalid'),
            ]);
        }

        $user->forceFill(['password' => $validated['password']])
            ->setRememberToken(Str::random(60));
        $user->save();
        DB::table('sessions')->where('user_id', $user->getKey())->delete();
        event(new PasswordReset($user));

        $request->session()->forget('password_reset_email');
        RateLimiter::clear($this->rateLimitKey($email));
        defer(fn () => $this->mail->sendPasswordChanged($user));

        return redirect()->route('login')
            ->with('success', __('app.messages.password_reset_complete'));
    }

    private function rateLimitKey(string $email): string
    {
        return 'password-reset:'.hash('sha256', $email);
    }
}
