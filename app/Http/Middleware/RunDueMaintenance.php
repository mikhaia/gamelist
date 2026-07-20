<?php

namespace App\Http\Middleware;

use App\Services\InactiveReminderService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

class RunDueMaintenance
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (Cache::add('maintenance:inactive-reminders-ran', true, now()->addDay())) {
            defer(fn () => app(InactiveReminderService::class)->sendDueReminders());
        }

        return $response;
    }
}
