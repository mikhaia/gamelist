<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminErrorLogService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ErrorLogController extends Controller
{
    public function __construct(private readonly AdminErrorLogService $logs) {}

    public function __invoke(Request $request): View
    {
        $levels = $this->logs->levels();
        $level = (string) $request->query('level', '');
        $level = array_key_exists($level, $levels) ? $level : null;
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 120);
        $result = $this->logs->read($level, $search);
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $entries = new LengthAwarePaginator(
            array_slice($result['entries'], ($page - 1) * $perPage, $perPage),
            count($result['entries']),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.errors.index', [
            'entries' => $entries,
            'files' => $result['files'],
            'stats' => $result['stats'],
            'truncated' => $result['truncated'],
            'levels' => $levels,
            'selectedLevel' => $level,
            'search' => $search,
        ]);
    }
}
