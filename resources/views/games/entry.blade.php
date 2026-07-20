@extends('layouts.app')

@section('title', $game->title)

@section('content')
    @php($listUrl = $isOwner ? route('lists.show', $game->gameList) : $game->gameList->public_path)

    <div class="mx-auto max-w-6xl">
        <a class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 transition hover:text-white" href="{{ $listUrl }}">
            <span class="material-symbols-outlined">arrow_back</span> К списку «{{ $game->gameList->name }}"
        </a>

        <section class="panel overflow-hidden p-0">
            <div class="grid gap-0 lg:grid-cols-[15rem_minmax(0,1fr)]">
                <div class="relative aspect-[3/4] overflow-hidden bg-gradient-to-br from-violet-950 via-slate-900 to-cyan-950 lg:aspect-auto">
                    @if ($game->cover_url)
                        <img src="{{ $game->cover_url }}" alt="Обложка {{ $game->title }}" class="h-full w-full object-cover">
                    @else
                        <span class="material-symbols-outlined absolute inset-0 grid place-items-center text-6xl text-white/15">sports_esports</span>
                    @endif
                </div>

                <div class="p-5 sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="status-chip"><span class="material-symbols-outlined text-sm">{{ $game->status->icon() }}</span>{{ $game->status->label() }}</span>
                                <span class="text-xs font-semibold text-slate-500">{{ $game->platform->label() }}</span>
                            </div>
                            <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">{{ $game->title }}</h1>
                        </div>
                        @if ($game->catalogGame || $isOwner)
                            <div class="flex shrink-0 flex-col items-stretch gap-2 sm:items-end">
                                @if ($game->catalogGame)
                                    <a class="button border-cyan-300/25 bg-cyan-500/10 text-cyan-200 hover:border-cyan-200/50 hover:bg-cyan-500/15" href="{{ route('games.show', $game->catalogGame) }}">
                                        <span class="material-symbols-outlined">travel_explore</span> Страница игры
                                    </a>
                                @endif
                                @if ($isOwner)
                                    <a class="button button-secondary" href="{{ route('games.edit', $game) }}"><span class="material-symbols-outlined">edit</span> Редактировать</a>
                                @endif
                            </div>
                        @endif
                    </div>

                    <a href="{{ route('profiles.show', $owner->login) }}" class="mt-6 inline-flex items-center gap-2.5 rounded-2xl border border-white/8 bg-white/[.025] px-3 py-2 transition hover:border-violet-400/30 hover:bg-violet-500/5">
                        <x-avatar :user="$owner" size="tiny" class="rounded-xl" />
                        <span>
                            <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-500">Владелец записи</span>
                            <span class="block text-sm font-extrabold text-slate-200">{{ '@'.$owner->login }}</span>
                        </span>
                    </a>

                    <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/8 bg-black/15 p-3.5">
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">{{ $isOwner ? 'Ваша оценка' : 'Оценка владельца' }}</dt>
                            @if ($game->catalogGame && $isOwner)
                                <dd class="mt-2">
                                    <form method="POST" action="{{ route('game-reviews.rating.update', $game->catalogGame) }}" class="flex items-center gap-2">
                                        @csrf @method('PATCH')
                                        <select class="min-w-0 flex-1 rounded-xl border border-amber-300/25 bg-amber-500/10 px-2.5 py-2 text-sm font-extrabold text-amber-100 outline-none transition focus:border-amber-200/60" name="rating" aria-label="Ваша оценка">
                                            <option value="">Без оценки</option>
                                            @for ($rating = 1; $rating <= 10; $rating++)
                                                <option value="{{ $rating }}" @selected((string) old('rating', $ownerReview?->rating) === (string) $rating)>{{ $rating }} / 10</option>
                                            @endfor
                                        </select>
                                        <button class="grid size-10 shrink-0 place-items-center rounded-xl border border-amber-300/25 bg-amber-500/10 text-amber-200 transition hover:border-amber-200/50 hover:bg-amber-500/20" title="Сохранить оценку" aria-label="Сохранить оценку"><span class="material-symbols-outlined">save</span></button>
                                    </form>
                                    @error('rating') <p class="field-error">{{ $message }}</p> @enderror
                                </dd>
                            @elseif ($game->catalogGame)
                                <dd class="mt-1.5 flex items-center gap-1.5 text-lg font-extrabold text-amber-200">
                                    <span class="material-symbols-outlined text-xl">trophy</span>{{ $ownerReview?->rating ? $ownerReview->rating.' / 10' : 'Не поставлена' }}
                                </dd>
                            @else
                                <dd class="mt-1.5 text-xs leading-5 text-slate-500">Доступна после привязки игры к каталогу.</dd>
                            @endif
                        </div>
                        <div class="rounded-2xl border border-white/8 bg-black/15 p-3.5">
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Время прохождения</dt>
                            <dd class="mt-1.5 text-lg font-extrabold text-slate-200">{{ $game->formattedTime($game->main_story_minutes) ?? 'Не указано' }}</dd>
                        </div>
                        <div class="rounded-2xl border border-white/8 bg-black/15 p-3.5">
                            <dt class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Список</dt>
                            <dd class="mt-1.5 truncate text-lg font-extrabold text-slate-200">{{ $game->gameList->name }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="space-y-6">
                <section class="panel">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-violet-500/10 text-violet-300"><span class="material-symbols-outlined">edit</span></span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-xl font-extrabold">Описание владельца</h2>
                            <p class="mt-1 text-xs text-slate-500">Личное описание этой записи, не из общего каталога.</p>
                        </div>
                    </div>
                    @if ($isOwner)
                        <details class="group mt-4" @if ($errors->has('notes')) open @endif>
                            <summary class="cursor-pointer list-none text-sm font-bold text-violet-300 transition hover:text-violet-200">Редактировать описание</summary>
                            <form method="POST" action="{{ route('games.personal-details.update', $game) }}" class="mt-4">
                                @csrf @method('PATCH')
                                <div data-markdown-editor data-preview-url="{{ route('game-reviews.preview') }}">
                                    <div class="flex items-center gap-1 border-b border-white/10">
                                        <button type="button" class="border-b-2 border-violet-400 px-3 py-2 text-xs font-bold text-white" data-markdown-write>Написать</button>
                                        <button type="button" class="border-b-2 border-transparent px-3 py-2 text-xs font-bold text-slate-500 transition hover:text-white" data-markdown-preview>Предпросмотр</button>
                                    </div>
                                    <div data-markdown-write-panel>
                                        <label class="sr-only" for="notes">Описание владельца</label>
                                        <textarea class="field min-h-36" id="notes" name="notes" maxlength="5000" placeholder="Что стоит знать об этой записи?" data-markdown-input>{{ old('notes', $game->notes) }}</textarea>
                                    </div>
                                    <div class="markdown-content mt-2 hidden min-h-36 rounded-2xl border border-white/10 bg-black/20 p-4" data-markdown-preview-panel>
                                        <p class="text-slate-500">Здесь появится предпросмотр.</p>
                                    </div>
                                </div>
                                @error('notes') <p class="field-error">{{ $message }}</p> @enderror
                                <button class="button button-secondary mt-3"><span class="material-symbols-outlined">save</span> Сохранить описание</button>
                            </form>
                        </details>
                    @endif
                    @if ($game->notes)
                        <div class="markdown-content mt-5">{!! $renderedNotes !!}</div>
                    @else
                        <p class="mt-5 text-sm text-slate-500">Владелец ещё ничего не добавил.</p>
                    @endif
                </section>

                <section class="panel">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-amber-500/10 text-amber-300"><span class="material-symbols-outlined">trophy</span></span>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-xl font-extrabold">Мнение владельца</h2>
                            <p class="mt-1 text-xs text-slate-500">То же мнение, что и на странице игры в каталоге.</p>
                        </div>
                    </div>
                    @if ($game->catalogGame && $isOwner)
                        <details class="group mt-4" @if ($errors->has('body')) open @endif>
                            <summary class="cursor-pointer list-none text-sm font-bold text-amber-300 transition hover:text-amber-200">Редактировать мнение</summary>
                            <form method="POST" action="{{ route('game-reviews.opinion.update', $game->catalogGame) }}" class="mt-4">
                                @csrf @method('PATCH')
                                <div data-markdown-editor data-preview-url="{{ route('game-reviews.preview') }}">
                                    <div class="flex items-center gap-1 border-b border-white/10">
                                        <button type="button" class="border-b-2 border-violet-400 px-3 py-2 text-xs font-bold text-white" data-markdown-write>Написать</button>
                                        <button type="button" class="border-b-2 border-transparent px-3 py-2 text-xs font-bold text-slate-500 transition hover:text-white" data-markdown-preview>Предпросмотр</button>
                                    </div>
                                    <div data-markdown-write-panel>
                                        <label class="sr-only" for="owner_opinion">Мнение владельца</label>
                                        <textarea class="field min-h-40" id="owner_opinion" name="body" maxlength="10000" placeholder="Что понравилось, а что заставило тянуться к Alt + F4?" data-markdown-input>{{ old('body', $ownerReview?->body) }}</textarea>
                                    </div>
                                    <div class="markdown-content mt-2 hidden min-h-40 rounded-2xl border border-white/10 bg-black/20 p-4" data-markdown-preview-panel>
                                        <p class="text-slate-500">Здесь появится предпросмотр.</p>
                                    </div>
                                </div>
                                @error('body') <p class="field-error">{{ $message }}</p> @enderror
                                <button class="button button-secondary mt-3"><span class="material-symbols-outlined">save</span> Сохранить мнение</button>
                            </form>
                        </details>
                    @endif
                    @if (! $game->catalogGame)
                        <p class="mt-5 text-sm text-slate-500">Мнение можно оставить только для игры, которая есть в каталоге.</p>
                    @elseif ($ownerReview?->body)
                        <div class="markdown-content mt-5">{!! $renderedOpinion !!}</div>
                    @else
                        <p class="mt-5 text-sm text-slate-500">Мнение пока не опубликовано.</p>
                    @endif
                </section>

                <section class="panel" id="comments">
                    <div class="flex items-center gap-3">
                        <span class="grid size-10 place-items-center rounded-xl bg-cyan-500/10 text-cyan-300"><span class="material-symbols-outlined">notifications</span></span>
                        <div>
                            <h2 class="text-xl font-extrabold">Обсуждение</h2>
                            <p class="mt-1 text-xs text-slate-500">Комментарии видны всем, кому доступна эта запись.</p>
                        </div>
                    </div>

                    @auth
                        <form method="POST" action="{{ route('games.comments.store', $game) }}" class="mt-5">
                            @csrf
                            <label class="label" for="comment_body">Новый комментарий</label>
                            <textarea class="field min-h-28" id="comment_body" name="body" maxlength="3000" required placeholder="Поделитесь мыслью об этой записи…">{{ old('parent_id') ? '' : old('body') }}</textarea>
                            <input type="hidden" name="parent_id" value="">
                            @error('body') <p class="field-error">{{ $message }}</p> @enderror
                            <button class="button button-primary mt-3"><span class="material-symbols-outlined">save</span> Отправить</button>
                        </form>
                    @else
                        <p class="mt-5 rounded-2xl border border-white/8 bg-black/15 p-4 text-sm text-slate-400">Чтобы оставить комментарий, <a href="{{ route('login') }}" class="font-bold text-violet-300 hover:text-violet-200">войдите в аккаунт</a>.</p>
                    @endauth

                    <div class="mt-7 space-y-4">
                        @forelse ($commentTree as $branch)
                            @include('games._comment', ['branch' => $branch, 'game' => $game, 'isOwner' => $isOwner, 'depth' => 0])
                        @empty
                            <p class="rounded-2xl border border-dashed border-white/10 px-4 py-7 text-center text-sm text-slate-500">Обсуждение пока не началось.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="panel">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-extrabold">Скриншоты</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $game->screenshots->count() }} из 12</p>
                        </div>
                        <span class="grid size-10 place-items-center rounded-xl bg-violet-500/10 text-violet-300"><span class="material-symbols-outlined">photo_library</span></span>
                    </div>

                    @if ($isOwner)
                        <form method="POST" enctype="multipart/form-data" action="{{ route('games.screenshots.store', $game) }}" class="mt-5">
                            @csrf
                            <label class="label" for="screenshots">Добавить скриншоты</label>
                            <input class="field text-xs file:mr-2 file:rounded-lg file:border-0 file:bg-violet-500/15 file:px-2.5 file:py-1.5 file:text-xs file:font-bold file:text-violet-300" id="screenshots" name="screenshots[]" type="file" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                            <p class="mt-2 text-[11px] leading-5 text-slate-500">До 12 изображений, не более 8 МБ каждое. JPEG, PNG, WebP или GIF.</p>
                            @error('screenshots') <p class="field-error">{{ $message }}</p> @enderror
                            @error('screenshots.*') <p class="field-error">{{ $message }}</p> @enderror
                            <button class="button button-secondary mt-3 w-full"><span class="material-symbols-outlined">image</span> Загрузить</button>
                        </form>
                    @endif

                    @if ($game->screenshots->isNotEmpty())
                        <div class="mt-5 grid grid-cols-2 gap-2">
                            @foreach ($game->screenshots as $index => $screenshot)
                                <figure class="group relative aspect-video overflow-hidden rounded-xl border border-white/8 bg-black/20">
                                    <button type="button" class="block h-full w-full cursor-zoom-in" data-screenshot-open data-screenshot-index="{{ $index }}" data-screenshot-url="{{ $screenshot->url }}" data-screenshot-alt="Скриншот {{ $index + 1 }} из игры {{ $game->title }}" aria-label="Открыть скриншот {{ $index + 1 }}">
                                        <img src="{{ $screenshot->url }}" alt="Скриншот игры {{ $game->title }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" loading="lazy">
                                    </button>
                                    @if ($isOwner)
                                        <form method="POST" action="{{ route('games.screenshots.destroy', [$game, $screenshot]) }}" class="absolute top-1.5 right-1.5">
                                            @csrf @method('DELETE')
                                            <button class="grid size-7 place-items-center rounded-lg border border-white/10 bg-black/70 text-slate-300 transition hover:border-red-300/40 hover:text-red-200" data-confirm="Удалить этот скриншот?" aria-label="Удалить скриншот" title="Удалить">
                                                <span class="material-symbols-outlined text-sm">delete</span>
                                            </button>
                                        </form>
                                    @endif
                                </figure>
                            @endforeach
                        </div>
                    @elseif (! $isOwner)
                        <p class="mt-5 text-sm text-slate-500">Владелец пока не добавил скриншоты.</p>
                    @endif
                </section>
            </aside>
        </div>
    </div>

    @if ($game->screenshots->isNotEmpty())
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
@endsection
