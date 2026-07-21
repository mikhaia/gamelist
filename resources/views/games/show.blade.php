@extends('layouts.app')

@section('title', $catalogGame->title)

@section('content')
<section class="grid gap-6 lg:grid-cols-[18rem_minmax(0,1fr)] lg:gap-8" data-game-page="{{ $catalogGame->id }}">
    <div class="mx-auto w-full max-w-72 lg:mx-0">
        <div class="glass aspect-[3/4] overflow-hidden rounded-3xl">
            @if ($coverUrl)
                <img src="{{ $coverUrl }}" alt="Обложка {{ $catalogGame->title }}" class="h-full w-full object-cover">
            @else
                <div class="grid h-full place-items-center bg-gradient-to-br from-violet-950 via-slate-900 to-cyan-950">
                    <span class="material-symbols-outlined text-7xl text-white/15">sports_esports</span>
                </div>
            @endif
        </div>
    </div>

    <div class="min-w-0">
        <h1 class="page-title max-w-4xl">{{ $catalogGame->title }}</h1>

        <div class="mt-5 flex flex-wrap gap-2">
            @if ($catalogGame->main_story_minutes)
                <span class="status-chip"><span class="material-symbols-outlined text-sm">schedule</span>Сюжет: {{ round($catalogGame->main_story_minutes / 60) }} ч</span>
            @endif
            @if ($catalogGame->completionist_minutes)
                <span class="status-chip"><span class="material-symbols-outlined text-sm">workspace_premium</span>100%: {{ round($catalogGame->completionist_minutes / 60) }} ч</span>
            @endif
            <span class="status-chip"><span class="material-symbols-outlined text-sm">playlist_add</span>{{ $totalAdditions }} {{ trans_choice('app.counts.additions', $totalAdditions) }}</span>
            <span class="status-chip border-amber-300/20 bg-amber-400/10 text-amber-200">
                <span class="material-symbols-outlined text-sm">workspace_premium</span>
                {{ $ratingAverage !== null ? number_format((float) $ratingAverage, 1, ',', ' ').' / 10' : 'Нет оценок' }}
                @if ($ratingCount)<span class="text-amber-100/50">· {{ $ratingCount }}</span>@endif
            </span>
        </div>

        @php
            $ageRatingLabel = $catalogGame->ageRatingLabel();
        @endphp
        @if (! empty($catalogGame->genres) || ! empty($catalogGame->platforms) || $ageRatingLabel)
            <div class="mt-5 space-y-3" data-rawg-metadata>
                @if (! empty($catalogGame->genres))
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-xs font-bold uppercase tracking-[.14em] text-slate-500">Жанры</span>
                        @foreach ($catalogGame->genres as $index => $genre)
                            @php
                                $genreSlug = $catalogGame->genre_slugs[$index] ?? null;
                            @endphp
                            @if ($genreSlug)
                                <a href="{{ route('search.index', ['genre' => $genreSlug, 'genre_name' => $genre]) }}" class="status-chip transition hover:border-violet-400/40 hover:bg-violet-500/15 hover:text-violet-100" title="Найти игры жанра {{ $genre }}">{{ $genre }}</a>
                            @else
                                <span class="status-chip">{{ $genre }}</span>
                            @endif
                        @endforeach
                    </div>
                @endif
                @if (! empty($catalogGame->platforms))
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-xs font-bold uppercase tracking-[.14em] text-slate-500">Платформы</span>
                        @foreach ($catalogGame->platforms as $index => $platform)
                            @php
                                $platformId = $catalogGame->platform_ids[$index] ?? null;
                            @endphp
                            @if ($platformId)
                                <a href="{{ route('search.index', ['platform' => $platformId, 'platform_name' => $platform]) }}" class="status-chip transition hover:border-cyan-400/35 hover:bg-cyan-500/10 hover:text-cyan-100" title="Найти игры для {{ $platform }}">{{ $platform }}</a>
                            @else
                                <span class="status-chip">{{ $platform }}</span>
                            @endif
                        @endforeach
                    </div>
                @endif
                @if ($ageRatingLabel)
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="mr-1 text-xs font-bold uppercase tracking-[.14em] text-slate-500">Возраст</span>
                        <span class="inline-grid min-h-10 min-w-10 place-items-center rounded-xl border border-amber-300/35 bg-amber-400/10 px-2 text-sm font-extrabold text-amber-100 shadow-lg shadow-amber-950/20" title="Возрастной рейтинг {{ $ageRatingLabel }}" aria-label="Возрастной рейтинг {{ $ageRatingLabel }}" data-age-rating="{{ $ageRatingLabel }}">{{ $ageRatingLabel }}</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="mt-7 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-5">
            @foreach (\App\Enums\GameStatus::cases() as $status)
                @php
                    $count = $statusCounts[$status->value];
                @endphp
                <div class="rounded-2xl border p-3 {{ $count ? 'border-violet-400/20 bg-violet-500/10' : 'border-white/7 bg-white/[.025]' }}" data-status-count="{{ $status->value }}" data-count="{{ $count }}">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-lg {{ $count ? 'text-violet-300' : 'text-slate-600' }}">{{ $status->icon() }}</span>
                        <strong class="text-lg text-white">{{ $count }}</strong>
                    </div>
                    <p class="mt-1 truncate text-[11px] font-semibold {{ $count ? 'text-slate-300' : 'text-slate-600' }}">{{ $status->label() }}</p>
                </div>
            @endforeach
        </div>

        <div class="panel p-4 mt-6">
            <div class="flex items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl border border-violet-400/20 bg-violet-500/10 text-violet-300">
                    <span class="material-symbols-outlined">playlist_add</span>
                </span>
                <div class="min-w-0 flex-1">
                    <h2 class="font-extrabold text-white">Добавить в свой список</h2>
                    @auth
                        @if ($userLists->isEmpty())
                            <p class="muted mt-1">Сначала создайте игровой список.</p>
                            <a href="{{ route('lists.create') }}" class="button button-secondary button-sm mt-4"><span class="material-symbols-outlined">add</span> Создать список</a>
                        @elseif ($availableLists->isEmpty())
                            <p class="muted mt-1">Игра уже добавлена во все ваши списки.</p>
                        @else
                            @php
                                $selectedList = $availableLists->first(fn ($list) => (string) $list->id === (string) old('game_list_id')) ?? $availableLists->first();
                                $selectedStatus = old('status', $selectedList->defaultStatus()->value);
                                $statusLabels = collect(\App\Enums\GameStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
                            @endphp
                            <form id="game-library-add-form" method="POST" action="{{ route('game-library.store', $catalogGame) }}" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto] xl:items-end" data-game-library-add data-status-labels='@json($statusLabels)'>
                                @csrf
                                <div class="min-w-0">
                                    <label class="label" for="game_list_id">Список</label>
                                    <select class="field" id="game_list_id" name="game_list_id" required data-game-library-list>
                                        @foreach ($availableLists as $list)
                                            <option value="{{ $list->id }}" data-statuses="{{ implode(',', $list->availableStatusValues()) }}" data-default-status="{{ $list->defaultStatus()->value }}" @selected($selectedList->is($list))>{{ $list->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('game_list_id') <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <div class="min-w-0">
                                    <label class="label" for="game_status">Статус</label>
                                    <select class="field" id="game_status" name="status" required data-game-library-status>
                                        @foreach ($selectedList->availableStatuses() as $status)
                                            <option value="{{ $status->value }}" @selected($selectedStatus === $status->value)>{{ $status->label() }}</option>
                                        @endforeach
                                    </select>
                                    @error('status') <p class="field-error">{{ $message }}</p> @enderror
                                </div>
                                <button class="button button-primary shrink-0 sm:col-span-2 xl:col-span-1"><span class="material-symbols-outlined">add</span> Добавить</button>
                            </form>
                            @if ($addedListsCount)
                                <p class="mt-3 text-[11px] text-slate-500">Уже находится в ваших списках: {{ $addedListsCount }}.</p>
                            @endif
                        @endif
                    @else
                        <p class="muted mt-1">Войдите, чтобы добавить игру в один из своих списков.</p>
                        <a href="{{ route('login') }}" class="button button-secondary button-sm mt-4"><span class="material-symbols-outlined">login</span> Войти</a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</section>

@if ($screenshots->isNotEmpty())
    <section class="mt-10" data-game-screenshots>
        <div class="mb-5">
            <span class="eyebrow"><span class="material-symbols-outlined">photo_library</span> Скриншоты</span>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($screenshots as $index => $screenshot)
                <button type="button" class="glass group relative block aspect-video w-full cursor-zoom-in overflow-hidden rounded-2xl text-left" data-screenshot-open data-screenshot-index="{{ $index }}" data-screenshot-url="{{ $screenshot['url'] }}" data-screenshot-alt="{{ $screenshot['caption'] }}" aria-label="Открыть: {{ $screenshot['caption'] }}">
                    <img src="{{ $screenshot['url'] }}" alt="{{ $screenshot['caption'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-[1.03]" loading="lazy">
                    @if ($screenshot['user'])
                        <span class="absolute right-2 bottom-2 max-w-[calc(100%-1rem)] truncate rounded-lg bg-black/70 px-2.5 py-1.5 text-[11px] font-bold text-white/90 backdrop-blur">{{ $screenshot['caption'] }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </section>

    @push('modals')
        <div class="fixed inset-0 z-[70] hidden items-center justify-center p-4 sm:p-8" role="dialog" aria-modal="true" aria-label="Просмотр скриншотов" aria-hidden="true" data-screenshot-modal>
            <button type="button" class="absolute inset-0 cursor-zoom-out bg-black/85 backdrop-blur-md" aria-label="Закрыть просмотр скриншота" data-screenshot-close></button>
            <div class="relative z-10 flex max-h-full w-full max-w-6xl flex-col overflow-hidden rounded-3xl border border-white/10 bg-[#080a12] shadow-2xl shadow-black/70">
                <div class="relative min-h-0 flex-1 bg-black/40">
                    <img src="" alt="" class="max-h-[80vh] min-h-48 w-full object-contain sm:min-h-80" data-screenshot-modal-image>
                    <button type="button" class="absolute top-3 right-3 grid size-11 cursor-pointer place-items-center rounded-xl border border-white/15 bg-black/65 text-white backdrop-blur transition hover:bg-black/85" aria-label="Закрыть" data-screenshot-close>
                        <span class="material-symbols-outlined">close</span>
                    </button>
                    <button type="button" class="absolute top-1/2 left-3 grid size-11 -translate-y-1/2 cursor-pointer place-items-center rounded-xl border border-white/15 bg-black/65 text-white backdrop-blur transition hover:bg-black/85" aria-label="Предыдущий скриншот" data-screenshot-previous>
                        <span class="material-symbols-outlined">arrow_back</span>
                    </button>
                    <button type="button" class="absolute top-1/2 right-3 grid size-11 -translate-y-1/2 cursor-pointer place-items-center rounded-xl border border-white/15 bg-black/65 text-white backdrop-blur transition hover:bg-black/85" aria-label="Следующий скриншот" data-screenshot-next>
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </button>
                </div>
                <div class="flex items-center justify-between gap-4 border-t border-white/10 px-4 py-3 text-xs text-slate-400 sm:px-5">
                    <span class="truncate" data-screenshot-caption></span>
                    <span class="shrink-0 font-bold text-slate-300" data-screenshot-counter></span>
                </div>
            </div>
        </div>
    @endpush
@endif

<section class="mt-10 grid gap-6 lg:grid-cols-2">
    <div class="panel">
        <div class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl border border-amber-300/20 bg-amber-400/10 text-amber-200">
                <span class="material-symbols-outlined">trophy</span>
            </span>
            <div>
                <h2 class="text-xl font-extrabold">Ваша оценка</h2>
                <p class="mt-1 text-xs text-slate-500">От 1 до 10. Оценка не зависит от мнения.</p>
            </div>
        </div>

        @auth
            @php
                $currentRating = old('rating', $userReview?->rating);
            @endphp
            <form method="POST" action="{{ route('game-reviews.rating.update', $catalogGame) }}" class="mt-6">
                @csrf @method('PATCH')
                <fieldset>
                    <legend class="sr-only">Оценка</legend>
                    <div class="flex flex-wrap gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="" class="peer sr-only" @checked(blank($currentRating))>
                            <span class="grid min-h-10 place-items-center rounded-xl border border-white/10 px-3 text-xs font-bold text-slate-500 transition peer-checked:border-white/25 peer-checked:bg-white/10 peer-checked:text-white">Без оценки</span>
                        </label>
                        @for ($rating = 1; $rating <= 10; $rating++)
                            <label class="cursor-pointer">
                                <input type="radio" name="rating" value="{{ $rating }}" class="peer sr-only" @checked((string) $currentRating === (string) $rating)>
                                <span class="grid size-10 place-items-center rounded-xl border border-white/10 text-sm font-extrabold text-slate-400 transition hover:border-amber-300/30 hover:text-amber-200 peer-checked:border-amber-300/40 peer-checked:bg-amber-400/15 peer-checked:text-amber-100">{{ $rating }}</span>
                            </label>
                        @endfor
                    </div>
                    @error('rating') <p class="field-error">{{ $message }}</p> @enderror
                </fieldset>
                <button class="button button-primary mt-5"><span class="material-symbols-outlined">save</span> Сохранить оценку</button>
            </form>
        @else
            <div class="mt-6 rounded-2xl border border-white/8 bg-white/[.025] p-5 text-center">
                <p class="muted">Войдите, чтобы поставить оценку.</p>
                <a href="{{ route('login') }}" class="button button-secondary button-sm mt-4"><span class="material-symbols-outlined">login</span> Войти</a>
            </div>
        @endauth
    </div>

    <div class="panel">
        <div class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-xl border border-violet-300/20 bg-violet-500/10 text-violet-200">
                <span class="material-symbols-outlined">edit</span>
            </span>
            <div>
                <h2 class="text-xl font-extrabold">Ваше мнение</h2>
                <p class="mt-1 text-xs text-slate-500">Поддерживается Markdown и предпросмотр.</p>
            </div>
        </div>

        @auth
            <form method="POST" action="{{ route('game-reviews.opinion.update', $catalogGame) }}" class="mt-6 space-y-5">
                @csrf @method('PATCH')
                <div data-markdown-editor data-preview-url="{{ route('game-reviews.preview') }}">
                    <div class="flex items-center gap-1 border-b border-white/10">
                        <button type="button" class="border-b-2 border-violet-400 px-3 py-2 text-xs font-bold text-white" data-markdown-write>Написать</button>
                        <button type="button" class="border-b-2 border-transparent px-3 py-2 text-xs font-bold text-slate-500 transition hover:text-white" data-markdown-preview>Предпросмотр</button>
                    </div>
                    <div data-markdown-write-panel>
                        <label class="sr-only" for="review_body">Мнение об игре</label>
                        <textarea class="field min-h-52 resize-y" id="review_body" name="body" maxlength="10000" placeholder="Что вам понравилось? Что можно было сделать лучше?" data-markdown-input>{{ old('body', $userReview?->body) }}</textarea>
                    </div>
                    <div class="markdown-content mt-2 hidden min-h-52 rounded-2xl border border-white/10 bg-black/20 p-4" data-markdown-preview-panel>
                        <p class="text-slate-500">Здесь появится предпросмотр.</p>
                    </div>
                    @error('body') <p class="field-error">{{ $message }}</p> @enderror
                </div>
                <button class="button button-primary"><span class="material-symbols-outlined">save</span> Сохранить мнение</button>
            </form>
        @else
            <div class="mt-6 rounded-2xl border border-white/8 bg-white/[.025] p-5 text-center">
                <p class="muted">Войдите, чтобы оставить мнение.</p>
                <a href="{{ route('login') }}" class="button button-secondary button-sm mt-4"><span class="material-symbols-outlined">login</span> Войти</a>
            </div>
        @endauth
    </div>
</section>

<aside class="panel mt-6 flex items-center justify-between gap-4 sm:px-6">
    <div>
        <p class="text-xs font-bold uppercase tracking-[.15em] text-slate-500">Общая оценка</p>
        <p class="mt-2 text-xs text-slate-500">{{ $ratingCount }} {{ trans_choice('app.counts.ratings', $ratingCount) }}</p>
    </div>
    <div class="flex items-end gap-2">
        <strong class="text-5xl font-extrabold text-amber-200">{{ $ratingAverage !== null ? number_format((float) $ratingAverage, 1, ',', ' ') : '—' }}</strong>
        <span class="pb-1 text-sm font-bold text-slate-500">/ 10</span>
    </div>
</aside>

<section class="mt-10">
    <div class="mb-5">
        <span class="eyebrow"><span class="material-symbols-outlined">edit</span> Мнения игроков</span>
        <h2 class="text-2xl font-extrabold">Отзывы</h2>
    </div>

    @if ($reviews->isEmpty())
        <div class="panel py-12 text-center">
            <span class="material-symbols-outlined text-5xl text-violet-300/30">edit</span>
            <p class="muted mt-3">Пока никто не написал мнение об этой игре.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($reviews as $review)
                <article class="panel" data-game-review>
                    <header class="flex items-start gap-3">
                        <x-avatar :user="$review->user" size="small" />
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('profiles.show', $review->user->login) }}" class="font-extrabold text-white transition hover:text-violet-200">{{ '@'.$review->user->login }}</a>
                            <p class="mt-0.5 text-[10px] font-semibold text-slate-600">{{ $review->updated_at->diffForHumans() }}</p>
                        </div>
                        @if ($review->rating)
                            <span class="rounded-xl border border-amber-300/20 bg-amber-400/10 px-3 py-2 text-sm font-extrabold text-amber-200">{{ $review->rating }} / 10</span>
                        @endif
                    </header>
                    <div class="markdown-content mt-5 border-t border-white/8 pt-5">{!! $review->rendered_body !!}</div>
                </article>
            @endforeach
        </div>
        <div class="mt-6">{{ $reviews->links() }}</div>
    @endif
</section>

@if (session('duplicateGame'))
    @push('modals')
        @include('games._duplicate_dialog', ['duplicateGame' => session('duplicateGame'), 'formId' => 'game-library-add-form'])
    @endpush
@endif
@endsection
