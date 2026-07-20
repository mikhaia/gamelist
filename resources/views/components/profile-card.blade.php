@props(['user', 'stats', 'isFriend' => false, 'compact' => false])

@php($recentAchievements = $user->achievements->sortByDesc('awarded_at')->take(3))

<article {{ $attributes->class('glass group relative overflow-hidden rounded-3xl') }}>
    @if ($user->profile_cover_url)
        <img src="{{ $user->profile_cover_url }}" alt="" class="absolute inset-0 h-full w-full object-cover opacity-75 transition duration-500 group-hover:scale-[1.02]">
        <div class="absolute inset-0 bg-gradient-to-r from-[#090b16]/60 via-[#090b16]/35 to-[#090b16]/10"></div>
    @else
        <div class="absolute inset-0 bg-gradient-to-r from-[#090b16]/95 via-[#090b16]/82 to-[#090b16]/55"></div>
    @endif

    <div class="relative flex flex-col gap-5 p-5 sm:flex-row sm:items-center sm:p-7">
        <x-avatar :user="$user" :size="$compact ? 'small' : 'large'" />

        <div class="min-w-0 flex-1">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <a href="{{ route('profiles.show', $user->login) }}" class="text-xl font-extrabold tracking-tight text-white transition hover:text-violet-200 {{ $compact ? '' : 'sm:text-3xl' }}">
                        {{ '@'.$user->login }}
                    </a>
                    <p class="mt-1 flex items-center gap-1.5 text-xs font-semibold {{ $user->isOnline() ? 'text-emerald-300' : 'text-slate-400' }}" data-profile-activity>
                        @if ($user->isOnline())<span class="size-1.5 rounded-full bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,.8)]"></span>@endif
                        {{ $user->activityLabel() }}
                    </p>
                </div>
                <div class="flex items-start gap-2">
                    @if (! $compact)
                        <div class="flex items-center gap-1.5">
                            @foreach ($recentAchievements as $award)
                                @php($achievement = $award->key)
                                <a href="{{ route('achievements.show', $user) }}" class="grid size-11 shrink-0 place-items-center rounded-2xl ring-1 transition hover:scale-105 {{ $achievement->colorClasses() }}" title="{{ $achievement->title() }} — {{ $award->awarded_at->format('d.m.Y') }}" aria-label="{{ $achievement->title() }}">
                                    <span class="material-symbols-outlined text-xl">{{ $achievement->icon() }}</span>
                                </a>
                            @endforeach
                            <a href="{{ route('achievements.show', $user) }}" class="inline-flex h-11 min-w-11 shrink-0 items-center justify-center gap-1 rounded-2xl border border-white/10 bg-black/15 px-2 text-amber-300 transition hover:border-amber-300/30 hover:bg-amber-500/10" title="Все достижения: {{ $user->achievements->count() }}" aria-label="Все достижения: {{ $user->achievements->count() }}">
                                <span class="material-symbols-outlined text-xl">workspace_premium</span>
                                <span class="text-xs font-extrabold">{{ $user->achievements->count() }}</span>
                            </a>
                        </div>
                    @endif
                    <x-friend-button :user="$user" :is-friend="$isFriend" compact />
                </div>
            </div>

            @if (collect($stats['statuses'])->filter()->isNotEmpty() || $stats['friends'] || $stats['public_lists'] || $stats['public_games'])
                @php($statusIconClasses = [
                    'want_to_play' => 'bg-violet-500/10 text-violet-300',
                    'installed' => 'bg-sky-500/10 text-sky-300',
                    'playing' => 'bg-cyan-500/10 text-cyan-300',
                    'completed' => 'bg-amber-500/10 text-amber-300',
                    'dropped' => 'bg-rose-500/10 text-rose-300',
                ])
                <div class="mt-3 flex flex-wrap gap-5 border-t border-white/10 pt-3">
                    @foreach (\App\Enums\GameStatus::cases() as $status)
                        @if ($stats['statuses'][$status->value])
                            <span class="inline-flex items-center gap-2" title="{{ $status->label() }}" aria-label="{{ $status->label() }}: {{ $stats['statuses'][$status->value] }}">
                                <span class="grid size-12 shrink-0 place-items-center rounded-2xl {{ $statusIconClasses[$status->value] }}">
                                    <span class="material-symbols-outlined text-2xl" aria-hidden="true">{{ $status->icon() }}</span>
                                </span>
                                <strong class="text-base font-extrabold text-white">{{ $stats['statuses'][$status->value] }}</strong>
                            </span>
                        @endif
                    @endforeach
                    @if ($stats['friends'] || $stats['public_lists'] || $stats['public_games'])
                        <div class="ml-auto flex flex-wrap gap-5">
                            @if ($stats['friends'])
                                <span class="inline-flex items-center gap-2" title="{{ trans_choice('app.counts.friends', $stats['friends']) }}" aria-label="{{ trans_choice('app.counts.friends', $stats['friends']) }}: {{ $stats['friends'] }}">
                                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-sky-500/10 text-sky-300"><span class="material-symbols-outlined text-2xl" aria-hidden="true">groups</span></span>
                                    <strong class="text-base font-extrabold text-white">{{ $stats['friends'] }}</strong>
                                </span>
                            @endif
                            @if ($stats['public_lists'])
                                <span class="inline-flex items-center gap-2" title="{{ trans_choice('app.counts.public_lists', $stats['public_lists']) }}" aria-label="{{ trans_choice('app.counts.public_lists', $stats['public_lists']) }}: {{ $stats['public_lists'] }}">
                                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-violet-500/10 text-violet-300"><span class="material-symbols-outlined text-2xl" aria-hidden="true">public</span></span>
                                    <strong class="text-base font-extrabold text-white">{{ $stats['public_lists'] }}</strong>
                                </span>
                            @endif
                            @if ($stats['public_games'])
                                <span class="inline-flex items-center gap-2" title="{{ trans_choice('app.counts.games', $stats['public_games']) }}" aria-label="{{ trans_choice('app.counts.games', $stats['public_games']) }}: {{ $stats['public_games'] }}">
                                    <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-emerald-500/10 text-emerald-300"><span class="material-symbols-outlined text-2xl" aria-hidden="true">stadia_controller</span></span>
                                    <strong class="text-base font-extrabold text-white">{{ $stats['public_games'] }}</strong>
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

        </div>
    </div>
</article>
