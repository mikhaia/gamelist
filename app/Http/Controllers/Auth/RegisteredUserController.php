<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $login = $request->input('login');
        if (is_string($login)) {
            $request->merge(['login' => strtolower(trim($login))]);
        }

        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => strtolower(trim($email)) ?: null]);
        }

        $validated = $request->validate([
            'login' => [
                'required', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9_]+$/',
                Rule::notIn(['catalog', 'friend', 'friends', 'game', 'games', 'history', 'lists', 'login', 'logout', 'notifications', 'profile', 'register', 'search', 'settings', 'up']),
                'unique:users,login',
            ],
            'email' => ['nullable', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'login.not_in' => __('app.errors.login_reserved'),
        ]);

        $user = User::create([
            'login' => $validated['login'],
            'email' => $validated['email'] ?? null,
            'password' => $validated['password'],
            'last_seen_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('lists.index')->with('success', __('app.messages.registered'));
    }
}
