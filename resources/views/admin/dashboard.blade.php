@extends('layouts.admin')

@section('title', 'Статистика')

@section('content')
<section aria-labelledby="users-and-games-stats">
    <h2 id="users-and-games-stats" class="sr-only">Статистика пользователей и игр</h2>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <x-admin.stat-card label="Пользователей всего" :value="$stats['users_total']" icon="groups" stat="users-total" />
        <x-admin.stat-card label="Регистраций за 7 дней" :value="$stats['users_7_days']" icon="person_add" tone="emerald" stat="users-7-days" />
        <x-admin.stat-card label="Регистраций за 30 дней" :value="$stats['users_30_days']" icon="calendar_today" tone="cyan" stat="users-30-days" />
        <x-admin.stat-card label="Игр в каталоге" :value="$stats['games_total']" icon="sports_esports" tone="amber" stat="games-total" />
        <x-admin.stat-card label="Игр добавлено за 7 дней" :value="$stats['games_7_days']" icon="add_circle" tone="emerald" stat="games-7-days" />
        <x-admin.stat-card label="Игр добавлено за 30 дней" :value="$stats['games_30_days']" icon="calendar_today" tone="cyan" stat="games-30-days" />
    </div>
</section>

<section class="mt-8" aria-labelledby="activity-charts">
    <div class="mb-4">
        <p class="admin-kicker">Последние 7 дней</p>
        <h2 id="activity-charts" class="mt-1 text-xl font-extrabold text-slate-950">Динамика проекта</h2>
    </div>
    <div class="grid gap-5 xl:grid-cols-2">
        <x-admin.bar-chart
            title="Добавление игр"
            subtitle="Новые игры, добавленные в каталог за каждый день"
            :series="$catalogGamesChart"
            icon="sports_esports"
            chart="catalog-games"
        />
        <x-admin.bar-chart
            title="Активность пользователей"
            subtitle="Пользователи по дню их последнего визита"
            :series="$userActivityChart"
            icon="groups"
            tone="cyan"
            chart="user-last-seen"
        />
    </div>
</section>

<section class="mt-8" aria-labelledby="storage-stats">
    <div class="mb-4 flex items-end justify-between gap-4">
        <div>
            <p class="admin-kicker">Storage</p>
            <h2 id="storage-stats" class="mt-1 text-xl font-extrabold text-slate-950">Загруженные файлы</h2>
        </div>
        <a href="{{ route('admin.files.index') }}" class="text-xs font-extrabold text-violet-700 transition hover:text-violet-900">Открыть файлы →</a>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-admin.stat-card label="Всего в хранилище" :value="$fileStats['total']['formatted']" icon="download" stat="files-total" layout="stacked" :percentage="$fileStats['total']['percentage']" :percentage-label="$fileStats['total']['percentage_formatted']" />
        <x-admin.stat-card label="Аватары" :value="$fileStats['avatars']['formatted']" icon="person" tone="cyan" stat="files-avatars" layout="stacked" :percentage="$fileStats['avatars']['percentage']" :percentage-label="$fileStats['avatars']['percentage_formatted']" />
        <x-admin.stat-card label="Обложки списков" :value="$fileStats['list-covers']['formatted']" icon="view_list" tone="emerald" stat="files-list-covers" layout="stacked" :percentage="$fileStats['list-covers']['percentage']" :percentage-label="$fileStats['list-covers']['percentage_formatted']" />
        <x-admin.stat-card label="Обложки игр" :value="$fileStats['game-covers']['formatted']" icon="sports_esports" tone="amber" stat="files-game-covers" layout="stacked" :percentage="$fileStats['game-covers']['percentage']" :percentage-label="$fileStats['game-covers']['percentage_formatted']" />
        <x-admin.stat-card label="Скриншоты" :value="$fileStats['screenshots']['formatted']" icon="photo_library" tone="violet" stat="files-screenshots" layout="stacked" :percentage="$fileStats['screenshots']['percentage']" :percentage-label="$fileStats['screenshots']['percentage_formatted']" />
    </div>
</section>

<div class="mt-8 grid gap-5 xl:grid-cols-2">
    <section class="admin-panel overflow-hidden" aria-labelledby="latest-users">
        <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-4 sm:px-6">
            <div>
                <p class="admin-kicker">Users</p>
                <h2 id="latest-users" class="mt-1 font-extrabold text-slate-950">Последние пользователи</h2>
            </div>
            <a href="{{ route('admin.users.index') }}" class="text-xs font-extrabold text-violet-700">Все →</a>
        </div>
        <div class="divide-y divide-slate-200/70">
            @forelse ($latestUsers as $user)
                <a href="{{ route('profiles.show', $user->login) }}" class="flex items-center justify-between gap-4 px-5 py-3.5 transition hover:bg-white/55 sm:px-6">
                    <span class="flex min-w-0 items-center gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-violet-500/10 text-violet-700"><span class="material-symbols-outlined text-lg">person</span></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-extrabold text-slate-900">{{ '@'.$user->login }}</span>
                            <span class="block truncate text-xs text-slate-400">{{ $user->email ?: 'Email не указан' }}</span>
                        </span>
                    </span>
                    <time class="shrink-0 text-[11px] font-bold text-slate-400">{{ $user->created_at->format('d.m.Y H:i') }}</time>
                </a>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-400">Пользователей пока нет.</p>
            @endforelse
        </div>
    </section>

    <section class="admin-panel overflow-hidden" aria-labelledby="latest-games">
        <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-4 sm:px-6">
            <div>
                <p class="admin-kicker">Catalog</p>
                <h2 id="latest-games" class="mt-1 font-extrabold text-slate-950">Последние игры</h2>
            </div>
            <a href="{{ route('admin.games.index') }}" class="text-xs font-extrabold text-violet-700">Все →</a>
        </div>
        <div class="divide-y divide-slate-200/70">
            @forelse ($latestGames as $game)
                <a href="{{ route('games.show', $game) }}" class="flex items-center justify-between gap-4 px-5 py-3.5 transition hover:bg-white/55 sm:px-6">
                    <span class="flex min-w-0 items-center gap-3">
                        <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-amber-500/10 text-amber-700"><span class="material-symbols-outlined text-lg">sports_esports</span></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-extrabold text-slate-900">{{ $game->title }}</span>
                            <span class="block truncate text-xs text-slate-400">{{ implode(', ', array_slice($game->platforms ?? [], 0, 3)) ?: 'Платформы не указаны' }}</span>
                        </span>
                    </span>
                    <time class="shrink-0 text-[11px] font-bold text-slate-400">{{ $game->created_at->format('d.m.Y H:i') }}</time>
                </a>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-400">Каталог пока пуст.</p>
            @endforelse
        </div>
    </section>

    <section class="admin-panel overflow-hidden" aria-labelledby="latest-screenshots">
        <div class="flex items-center justify-between border-b border-slate-200/70 px-5 py-4 sm:px-6">
            <div>
                <p class="admin-kicker">Media</p>
                <h2 id="latest-screenshots" class="mt-1 font-extrabold text-slate-950">Последние скриншоты</h2>
            </div>
            <a href="{{ route('admin.files.index', ['type' => 'screenshots']) }}" class="text-xs font-extrabold text-violet-700">Все →</a>
        </div>
        <div class="divide-y divide-slate-200/70">
            @forelse ($latestScreenshots as $screenshot)
                <div class="flex items-center justify-between gap-4 px-5 py-3.5 sm:px-6">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-extrabold text-slate-900">{{ $screenshot->game?->title ?? 'Удалённая игра' }}</span>
                        <span class="block truncate text-xs text-slate-400">{{ $screenshot->game?->gameList?->user ? '@'.$screenshot->game->gameList->user->login : 'Владелец недоступен' }}</span>
                    </span>
                    <span class="flex shrink-0 items-center gap-3">
                        <time class="hidden text-[11px] font-bold text-slate-400 sm:block">{{ $screenshot->created_at->format('d.m.Y H:i') }}</time>
                        <a href="{{ route('admin.files.download', ['type' => 'screenshots', 'id' => $screenshot->id]) }}" class="grid size-9 cursor-pointer place-items-center rounded-xl bg-slate-950 text-white transition hover:bg-violet-700" title="Скачать" aria-label="Скачать скриншот"><span class="material-symbols-outlined text-lg">download</span></a>
                    </span>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-400">Скриншотов пока нет.</p>
            @endforelse
        </div>
    </section>

    <section class="admin-panel overflow-hidden" aria-labelledby="latest-comments">
        <div class="border-b border-slate-200/70 px-5 py-4 sm:px-6">
            <p class="admin-kicker">Community</p>
            <h2 id="latest-comments" class="mt-1 font-extrabold text-slate-950">Последние комментарии</h2>
        </div>
        <div class="divide-y divide-slate-200/70">
            @forelse ($latestComments as $comment)
                <div class="px-5 py-3.5 sm:px-6">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-xs font-extrabold text-violet-700">{{ $comment->user ? '@'.$comment->user->login : 'Удалённый пользователь' }}</span>
                        <time class="text-[11px] font-bold text-slate-400">{{ $comment->created_at->format('d.m.Y H:i') }}</time>
                    </div>
                    <p class="mt-1.5 text-sm leading-6 text-slate-600">{{ \Illuminate\Support\Str::limit($comment->body, 140) }}</p>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-400">Комментариев пока нет.</p>
            @endforelse
        </div>
    </section>

    <section class="admin-panel overflow-hidden xl:col-span-2" aria-labelledby="latest-reviews">
        <div class="border-b border-slate-200/70 px-5 py-4 sm:px-6">
            <p class="admin-kicker">Reviews</p>
            <h2 id="latest-reviews" class="mt-1 font-extrabold text-slate-950">Последние обзоры</h2>
        </div>
        <div class="grid divide-y divide-slate-200/70 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
            @forelse ($latestReviews as $review)
                <div class="px-5 py-4 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-extrabold text-slate-900">{{ $review->catalogGame?->title ?? 'Удалённая игра' }}</span>
                            <span class="mt-0.5 block text-xs font-bold text-violet-700">{{ $review->user ? '@'.$review->user->login : 'Удалённый пользователь' }}</span>
                        </span>
                        @if ($review->rating)
                            <span class="shrink-0 rounded-xl bg-amber-500/10 px-2.5 py-1.5 text-xs font-extrabold text-amber-800">{{ $review->rating }} / 10</span>
                        @endif
                    </div>
                    <p class="mt-2 text-sm leading-6 text-slate-600">{{ $review->body ? \Illuminate\Support\Str::limit($review->body, 180) : 'Текст обзора не добавлен.' }}</p>
                    <time class="mt-2 block text-[11px] font-bold text-slate-400">{{ $review->created_at->format('d.m.Y H:i') }}</time>
                </div>
            @empty
                <p class="px-6 py-10 text-center text-sm text-slate-400 lg:col-span-2">Обзоров пока нет.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
