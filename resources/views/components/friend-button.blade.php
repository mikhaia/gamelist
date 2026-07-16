@props(['user', 'isFriend' => false, 'compact' => false])

@if (! auth()->check())
    <a href="{{ route('login') }}" class="button button-secondary {{ $compact ? 'button-sm' : '' }}">
        <span class="material-symbols-outlined">person_add</span> Добавить в друзья
    </a>
@elseif (! auth()->user()->is($user))
    @if ($isFriend)
        <form method="POST" action="{{ route('friends.destroy', $user) }}">
            @csrf @method('DELETE')
            <button class="button button-secondary {{ $compact ? 'button-sm' : '' }}" title="Удалить из друзей">
                <span class="material-symbols-outlined">check</span> В друзьях
            </button>
        </form>
    @else
        <form method="POST" action="{{ route('friends.store', $user) }}">
            @csrf
            <button class="button button-primary {{ $compact ? 'button-sm' : '' }}">
                <span class="material-symbols-outlined">person_add</span> Добавить в друзья
            </button>
        </form>
    @endif
@endif
