<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function destroy(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        $request->user()->notifications()->whereKey($notification)->firstOrFail()->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => __('app.messages.notification_removed')]);
        }

        return back();
    }

    public function clear(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->notifications()->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => __('app.messages.notifications_cleared')]);
        }

        return back();
    }
}
