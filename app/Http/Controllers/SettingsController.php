<?php

namespace App\Http\Controllers;

use App\Services\AchievementService;
use App\Services\CoverImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AchievementService $achievements,
        private readonly CoverImageService $images,
    ) {}

    public function edit(): View
    {
        return view('settings.edit');
    }

    public function avatarEdit(): View
    {
        return view('settings.avatar');
    }

    public function avatar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:8192'],
        ]);

        $path = $this->images->storeAvatar($validated['avatar'], $request->user()->avatar_path);
        $request->user()->update(['avatar_path' => $path]);
        $this->achievements->evaluate($request->user());

        return back()->with('success', __('app.messages.avatar_updated'));
    }

    public function email(Request $request): RedirectResponse
    {
        $email = $request->input('email');
        if (is_string($email)) {
            $request->merge(['email' => strtolower(trim($email)) ?: null]);
        }

        $validated = $request->validate([
            'email' => [
                'nullable',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->user()->getKey()),
            ],
        ]);

        $request->user()->update(['email' => $validated['email'] ?? null]);
        $this->achievements->evaluate($request->user());

        return back()->with('success', __('app.messages.email_updated'));
    }

    public function profileCover(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'profile_cover' => ['required', 'image', 'max:8192'],
        ]);

        $path = $this->images->storeProfileCover($validated['profile_cover'], $request->user()->profile_cover_path);
        $request->user()->update(['profile_cover_path' => $path]);

        return back()->with('success', __('app.messages.profile_cover_updated'));
    }

    public function password(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $validated['password']]);

        return back()->with('success', __('app.messages.password_updated'));
    }
}
