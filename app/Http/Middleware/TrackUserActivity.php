<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            $user->forceFill([
                'last_seen_at' => now(),
                'inactive_reminder_sent_at' => null,
            ])->saveQuietly();
        }

        return $next($request);
    }
}
