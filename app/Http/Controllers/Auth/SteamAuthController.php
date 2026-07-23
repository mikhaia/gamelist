<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\SteamOpenId;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SteamAuthController extends Controller
{
    private const SESSION_STATE = 'steam_openid.state';

    private const SESSION_ACTION = 'steam_openid.action';

    public function __construct(
        private readonly SteamOpenId $steam,
        private readonly AchievementService $achievements,
    ) {}

    public function redirect(Request $request): RedirectResponse
    {
        $state = Str::random(48);
        $returnTo = $this->callbackUrl($state);
        $request->session()->put([
            self::SESSION_STATE => $state,
            self::SESSION_ACTION => $request->user() ? 'link' : 'login',
        ]);

        return redirect()->away($this->steam->authenticationUrl(
            $returnTo,
            $this->realm($returnTo),
        ));
    }

    public function callback(Request $request): RedirectResponse
    {
        $state = (string) $request->query('state', '');
        $expectedState = (string) $request->session()->pull(self::SESSION_STATE, '');
        $action = (string) $request->session()->pull(self::SESSION_ACTION, 'login');

        if ($state === '' || $expectedState === '' || ! hash_equals($expectedState, $state)) {
            return $this->failed($request, __('app.errors.steam_auth_expired'));
        }

        $steamId = $this->steam->verify($request, $this->callbackUrl($state));
        if (! $steamId) {
            return $this->failed($request, __('app.errors.steam_auth_failed'));
        }

        if ($action === 'link') {
            return $this->link($request, $steamId);
        }

        $user = User::query()->where('steam_id', $steamId)->first();
        if (! $user) {
            $user = User::query()->create([
                'login' => $this->uniqueLogin($steamId),
                'password' => null,
                'steam_id' => $steamId,
                'steam_connected_at' => now(),
                'last_seen_at' => now(),
            ]);
            $this->achievements->evaluate($user);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('lists.index'))->with('success', __('app.messages.steam_login_complete'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        if (! $request->user()->hasLocalPassword()) {
            throw ValidationException::withMessages([
                'steam' => __('app.errors.steam_password_required'),
            ]);
        }

        $request->user()->update([
            'steam_id' => null,
            'steam_connected_at' => null,
        ]);

        return back()->with('success', __('app.messages.steam_disconnected'));
    }

    private function link(Request $request, string $steamId): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->withErrors([
                'steam' => __('app.errors.steam_auth_expired'),
            ]);
        }

        $alreadyConnected = User::query()
            ->where('steam_id', $steamId)
            ->whereKeyNot($user->id)
            ->exists();
        if ($alreadyConnected) {
            return redirect()->route('settings.edit')->withErrors([
                'steam' => __('app.errors.steam_already_connected'),
            ]);
        }

        $user->update([
            'steam_id' => $steamId,
            'steam_connected_at' => now(),
        ]);

        return redirect()->route('settings.edit')->with('success', __('app.messages.steam_connected'));
    }

    private function failed(Request $request, string $message): RedirectResponse
    {
        return redirect()->route($request->user() ? 'settings.edit' : 'login')
            ->withErrors(['steam' => $message]);
    }

    private function callbackUrl(string $state): string
    {
        $callback = trim((string) config('services.steam.return_url')) ?: route('steam.callback');

        return $callback.(str_contains($callback, '?') ? '&' : '?').http_build_query([
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    private function realm(string $returnTo): string
    {
        $configured = trim((string) config('services.steam.realm'));
        if ($configured !== '') {
            return $configured;
        }

        $parts = parse_url($returnTo);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').$port;
    }

    private function uniqueLogin(string $steamId): string
    {
        $base = 'steam_'.$steamId;
        $login = $base;
        $suffix = 2;

        while (User::query()->where('login', $login)->exists()) {
            $suffixText = '_'.$suffix++;
            $login = Str::limit($base, 32 - strlen($suffixText), '').$suffixText;
        }

        return $login;
    }
}
