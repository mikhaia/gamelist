@foreach ($games as $catalogGame)
    @php
        $alreadyAdded = $gameList && (isset($addedHltbIds[(string) $catalogGame->hltb_id]) || isset($addedTitles[$catalogGame->normalized_title]));
        $pickerTitle = auth()->check() ? 'Выбрать список для '.$catalogGame->title : 'Войти, чтобы добавить '.$catalogGame->title;
    @endphp
    <article class="glass group relative overflow-hidden rounded-3xl" data-catalog-browser-card data-catalog-id="{{ $catalogGame->id }}">
        <div class="relative aspect-[3/4] overflow-hidden bg-gradient-to-br from-violet-950 via-slate-900 to-cyan-950">
            <a href="{{ route('games.show', $catalogGame) }}" class="block h-full" aria-label="Открыть страницу игры {{ $catalogGame->title }}">
                @if ($catalogGame->cover_url)
                    <img src="{{ $catalogGame->cover_url }}" alt="Обложка {{ $catalogGame->title }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy">
                @else
                    <span class="grid h-full place-items-center"><span class="material-symbols-outlined text-5xl text-white/15">sports_esports</span></span>
                @endif
                <span class="absolute inset-x-0 bottom-0 h-28 bg-gradient-to-t from-[#0b0d18] to-transparent"></span>
            </a>
            @if ($gameList)
                <button
                    type="button"
                    class="absolute right-3 top-3 z-10 grid size-11 cursor-pointer place-items-center rounded-2xl border backdrop-blur-xl transition {{ $alreadyAdded ? 'border-emerald-400/25 bg-emerald-950/80 text-emerald-300' : 'border-white/15 bg-black/70 text-white hover:scale-105 hover:border-violet-400/60 hover:bg-violet-500' }}"
                    title="{{ $alreadyAdded ? 'Уже в списке' : 'Добавить в '.$gameList->name }}"
                    aria-label="{{ $alreadyAdded ? 'Уже в списке' : 'Добавить '.$catalogGame->title }}"
                    data-quick-add="{{ route('catalog.add', [$gameList, $catalogGame]) }}"
                    @disabled($alreadyAdded)
                >
                    <span class="material-symbols-outlined" data-quick-add-icon>{{ $alreadyAdded ? 'check' : 'add' }}</span>
                </button>
            @else
                <button
                    type="button"
                    class="absolute right-3 top-3 z-10 grid size-11 cursor-pointer place-items-center rounded-2xl border border-white/15 bg-black/70 text-white backdrop-blur-xl transition hover:scale-105 hover:border-violet-400/60 hover:bg-violet-500"
                    title="{{ $pickerTitle }}"
                    aria-label="{{ $pickerTitle }}"
                    data-catalog-list-picker
                    data-catalog-id="{{ $catalogGame->id }}"
                    data-catalog-title="{{ $catalogGame->title }}"
                    @guest data-login-url="{{ route('login') }}" @endguest
                >
                    <span class="material-symbols-outlined">add</span>
                </button>
            @endif
        </div>
        <div class="p-4">
            <h2 class="line-clamp-2 min-h-11 text-sm font-extrabold leading-5 sm:text-base"><a href="{{ route('games.show', $catalogGame) }}" class="transition hover:text-violet-200">{{ $catalogGame->title }}</a></h2>
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
