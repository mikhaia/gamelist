@foreach ($games as $catalogGame)
    @php($alreadyAdded = isset($addedHltbIds[(string) $catalogGame->hltb_id]) || isset($addedTitles[$catalogGame->normalized_title]))
    <article class="glass group relative overflow-hidden rounded-3xl" data-catalog-browser-card data-catalog-id="{{ $catalogGame->id }}">
        <div class="relative aspect-[3/4] overflow-hidden bg-gradient-to-br from-violet-950 via-slate-900 to-cyan-950">
            @if ($catalogGame->cover_url)
                <img src="{{ $catalogGame->cover_url }}" alt="Обложка {{ $catalogGame->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy">
            @else
                <div class="grid h-full place-items-center"><span class="material-symbols-outlined text-5xl text-white/15">sports_esports</span></div>
            @endif
            <div class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-[#0b0d18] to-transparent"></div>
            <button
                type="button"
                class="absolute right-3 top-3 grid size-11 place-items-center rounded-2xl border backdrop-blur-xl transition {{ $alreadyAdded ? 'border-emerald-400/25 bg-emerald-950/80 text-emerald-300' : 'border-white/15 bg-black/70 text-white hover:scale-105 hover:border-violet-400/60 hover:bg-violet-500' }}"
                title="{{ $alreadyAdded ? 'Уже в списке' : 'Добавить в '.$gameList->name }}"
                aria-label="{{ $alreadyAdded ? 'Уже в списке' : 'Добавить '.$catalogGame->title }}"
                data-quick-add="{{ route('catalog.add', [$gameList, $catalogGame]) }}"
                @disabled($alreadyAdded)
            >
                <span class="material-symbols-outlined" data-quick-add-icon>{{ $alreadyAdded ? 'check' : 'add' }}</span>
            </button>
        </div>
        <div class="p-4">
            <h2 class="line-clamp-2 min-h-11 text-sm font-extrabold leading-5 sm:text-base">{{ $catalogGame->title }}</h2>
            <div class="mt-3 flex flex-wrap gap-2 text-[10px] font-semibold text-slate-500">
                @if ($catalogGame->main_story_minutes)
                    <span class="status-chip"><span class="material-symbols-outlined text-xs">schedule</span>Сюжет: {{ round($catalogGame->main_story_minutes / 60) }} ч</span>
                @endif
                @if ($catalogGame->completionist_minutes)
                    <span class="status-chip"><span class="material-symbols-outlined text-xs">workspace_premium</span>100%: {{ round($catalogGame->completionist_minutes / 60) }} ч</span>
                @endif
            </div>
        </div>
    </article>
@endforeach
