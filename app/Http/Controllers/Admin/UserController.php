<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', Rule::in(['login', 'last_seen_at', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $query = trim((string) ($validated['q'] ?? ''));
        $sort = $validated['sort'] ?? 'created_at';
        $direction = $validated['direction'] ?? ($sort === 'login' ? 'asc' : 'desc');

        $users = User::query()
            ->when($query !== '', function ($users) use ($query): void {
                $users->where(function ($search) use ($query): void {
                    $search->where('login', 'like', '%'.$query.'%')
                        ->orWhere('email', 'like', '%'.$query.'%');
                });
            })
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'query', 'sort', 'direction'));
    }
}
