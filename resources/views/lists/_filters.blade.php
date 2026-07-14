@php($publicView = $publicView ?? false)
@php($filterRoute = $publicView ? route('public.lists.show', [$gameList->user->login, $gameList->slug]) : route('lists.show', $gameList))
@php($exportRoute = $publicView ? route('public.lists.export', [$gameList->user->login, $gameList->slug]) : route('lists.export', $gameList))

<div class="mb-6 flex flex-col gap-3 rounded-2xl border border-white/8 bg-white/[.035] p-3 lg:flex-row lg:items-center lg:justify-between">
    <form method="GET" action="{{ $filterRoute }}" class="flex min-w-0 flex-wrap items-center gap-2">
        <span class="mr-1 flex items-center gap-1.5 text-xs font-bold text-slate-500"><span class="material-symbols-outlined text-base">filter_alt</span> Статус</span>
        <a href="{{ $filterRoute }}" class="rounded-xl border px-3 py-2 text-xs font-semibold transition {{ $selectedStatuses === [] ? 'border-violet-400/40 bg-violet-500/15 text-violet-200' : 'border-white/8 bg-black/15 text-slate-500 hover:text-white' }}">Все</a>
        @foreach ($statuses as $status)
            <label class="cursor-pointer">
                <input type="checkbox" name="status[]" value="{{ $status->value }}" class="peer sr-only" @checked(in_array($status->value, $selectedStatuses, true)) onchange="this.form.submit()">
                <span class="flex items-center gap-1.5 rounded-xl border border-white/8 bg-black/15 px-3 py-2 text-xs font-semibold text-slate-500 transition hover:text-white peer-checked:border-violet-400/40 peer-checked:bg-violet-500/15 peer-checked:text-violet-200">
                    <span class="material-symbols-outlined text-sm">{{ $status->icon() }}</span>{{ $status->label() }}
                </span>
            </label>
        @endforeach
    </form>
    <a href="{{ $exportRoute }}?{{ http_build_query(['status' => $selectedStatuses]) }}" class="button button-secondary button-sm shrink-0">
        <span class="material-symbols-outlined">download</span> Экспортировать {{ $gameList->games->count() }}
    </a>
</div>
