@extends('layouts.admin')

@section('title', 'Пользователи')

@section('content')
<form method="GET" action="{{ route('admin.users.index') }}" class="admin-panel mb-5 flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
    <div class="relative min-w-0 flex-1">
        <span class="material-symbols-outlined pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-lg text-slate-400">search</span>
        <input class="admin-field pl-11" name="q" value="{{ $query }}" placeholder="Поиск по логину или email" aria-label="Поиск пользователей">
    </div>
    <input type="hidden" name="sort" value="{{ $sort }}">
    <input type="hidden" name="direction" value="{{ $direction }}">
    <button class="admin-button"><span class="material-symbols-outlined">search</span> Найти</button>
    @if ($query !== '')
        <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl px-3 text-sm font-bold text-slate-500 transition hover:bg-white/70 hover:text-slate-900">Сбросить</a>
    @endif
</form>

<section class="admin-panel overflow-hidden" aria-label="Таблица пользователей">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[52rem] border-collapse text-left">
            <thead class="border-b border-slate-200/80 bg-white/45 text-xs font-extrabold uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-4 sm:px-6"><x-admin.sort-link field="login" label="Login" :sort="$sort" :direction="$direction" /></th>
                    <th class="px-5 py-4 text-slate-500 sm:px-6">Email</th>
                    <th class="px-5 py-4 sm:px-6"><x-admin.sort-link field="last_seen_at" label="Последняя активность" :sort="$sort" :direction="$direction" default-direction="desc" /></th>
                    <th class="px-5 py-4 sm:px-6"><x-admin.sort-link field="created_at" label="Регистрация" :sort="$sort" :direction="$direction" default-direction="desc" /></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/70">
                @forelse ($users as $user)
                    <tr class="transition hover:bg-white/55" data-admin-user="{{ $user->login }}">
                        <td class="px-5 py-4 sm:px-6">
                            <a href="{{ route('profiles.show', $user->login) }}" class="inline-flex items-center gap-2 font-extrabold text-slate-900 transition hover:text-violet-700">
                                {{ '@'.$user->login }}
                                @if ($user->is_admin)<span class="rounded-full bg-violet-500/10 px-2 py-0.5 text-[9px] uppercase tracking-wider text-violet-700">Admin</span>@endif
                            </a>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-600 sm:px-6">{{ $user->email ?: '—' }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600 sm:px-6">{{ $user->last_seen_at?->format('d.m.Y H:i') ?? 'Никогда' }}</td>
                        <td class="px-5 py-4 text-sm font-semibold text-slate-600 sm:px-6">{{ $user->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-16 text-center text-sm text-slate-400">Пользователи не найдены.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if ($users->hasPages())
    <div class="mt-5">{{ $users->links() }}</div>
@endif
@endsection
