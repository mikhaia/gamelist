<?php

namespace App\Http\Controllers;

use App\Services\CoverImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private readonly CoverImageService $images) {}

    public function edit(): View
    {
        return view('settings.edit');
    }

    public function avatar(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'avatar' => ['required', 'image', 'max:8192'],
        ]);

        $path = $this->images->storeUpload(
            $validated['avatar'],
            $request->user()->avatar_path,
            'avatars',
            512,
            512,
        );
        $request->user()->update(['avatar_path' => $path]);

        return back()->with('success', __('app.messages.avatar_updated'));
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
