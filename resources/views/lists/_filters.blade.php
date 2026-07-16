@php
    $publicView = $publicView ?? false;
    $sort = $sort ?? 'added_at';
    $filterRoute = $publicView ? route('public.lists.show', [$gameList->user->login, $gameList->slug]) : route('lists.show', $gameList);
    $allStatusesRoute = $filterRoute.($sort === 'completed_at' ? '?sort=completed_at' : '');
    $clipboardText = $gameList->games->map(fn ($game) => '- '.$game->title)->implode("\n");
@endphp

<div class="mb-6 flex flex-col gap-3 rounded-2xl border border-white/8 bg-white/[.035] p-3 lg:flex-row lg:items-center lg:justify-between">
    <form method="GET" action="{{ $filterRoute }}" class="flex min-w-0 flex-wrap items-center gap-2">
        @if ($sort !== 'added_at')<input type="hidden" name="sort" value="{{ $sort }}">@endif
        <span class="mr-1 flex items-center gap-1.5 text-xs font-bold text-slate-500"><span class="material-symbols-outlined text-base">filter_alt</span> Статус</span>
        <a href="{{ $allStatusesRoute }}" class="rounded-xl border px-3 py-2 text-xs font-semibold transition {{ $selectedStatuses === [] ? 'border-violet-400/40 bg-violet-500/15 text-violet-200' : 'border-white/8 bg-black/15 text-slate-500 hover:text-white' }}">Все</a>
        @foreach ($statuses as $status)
            <label class="cursor-pointer">
                <input type="checkbox" name="status[]" value="{{ $status->value }}" class="peer sr-only" @checked(in_array($status->value, $selectedStatuses, true)) onchange="this.form.submit()">
                <span class="flex items-center gap-1.5 rounded-xl border border-white/8 bg-black/15 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:text-white peer-checked:border-violet-400/40 peer-checked:bg-violet-500/15 peer-checked:text-violet-200">
                    <span class="material-symbols-outlined text-sm">{{ $status->icon() }}</span>{{ $status->label() }}
                </span>
            </label>
        @endforeach
    </form>
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <button type="button" class="button button-secondary button-sm shrink-0" data-copy="{{ $clipboardText }}" data-copied="Список скопирован" data-list-copy @disabled($gameList->games->isEmpty())>
            <span class="material-symbols-outlined">content_copy</span><span data-copy-label>Скопировать список ({{ $gameList->games->count() }})</span>
        </button>
        <form method="GET" action="{{ $filterRoute }}" class="relative shrink-0">
            @foreach ($selectedStatuses as $selectedStatus)
                <input type="hidden" name="status[]" value="{{ $selectedStatus }}">
            @endforeach
            <span class="material-symbols-outlined pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-base text-slate-500">sort</span>
            <select name="sort" class="min-h-9 w-full cursor-pointer appearance-none rounded-xl border border-white/8 bg-black/20 py-2 pr-9 pl-9 text-xs font-semibold text-slate-300 outline-none transition hover:border-white/15 focus:border-violet-400/40 sm:w-auto" aria-label="Сортировка списка" onchange="this.form.submit()">
                <option value="added_at" @selected($sort === 'added_at')>По дате добавления</option>
                <option value="completed_at" @selected($sort === 'completed_at')>По дате прохождения</option>
            </select>
            <span class="material-symbols-outlined pointer-events-none absolute top-1/2 right-2.5 -translate-y-1/2 text-base text-slate-600">expand_more</span>
        </form>
    </div>
</div>
