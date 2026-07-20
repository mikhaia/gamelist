@php($comment = $branch['comment'])
@php($canReply = auth()->check() && ($comment->hidden_at === null || $isOwner || $comment->user_id === auth()->id()))

<article id="comment-{{ $comment->id }}" class="{{ $depth > 0 ? 'ml-4 border-l border-white/10 pl-4 sm:ml-7 sm:pl-5' : '' }}">
    <div class="rounded-2xl border border-white/8 bg-black/15 p-4 {{ $comment->hidden_at && $isOwner ? 'opacity-55' : '' }}">
        <div class="flex items-start gap-3">
            <x-avatar :user="$comment->user" size="tiny" class="rounded-xl" />
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <a href="{{ route('profiles.show', $comment->user->login) }}" class="text-sm font-extrabold text-slate-200 transition hover:text-violet-200">{{ '@'.$comment->user->login }}</a>
                    <span class="text-[11px] text-slate-600">{{ $comment->created_at->diffForHumans() }}</span>
                    @if ($comment->hidden_at && $isOwner)
                        <span class="rounded-full border border-amber-300/20 bg-amber-500/10 px-2 py-0.5 text-[10px] font-bold text-amber-200">Скрыт</span>
                    @endif
                </div>
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-300">{{ $comment->body }}</p>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-white/7 pt-3">
            @if ($canReply)
                <details class="group">
                    <summary class="cursor-pointer list-none text-xs font-bold text-slate-500 transition hover:text-violet-200">Ответить</summary>
                    <form method="POST" action="{{ route('games.comments.store', $game) }}" class="mt-3 w-full sm:min-w-96">
                        @csrf
                        <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                        <label class="sr-only" for="reply-{{ $comment->id }}">Ответ для {{ '@'.$comment->user->login }}</label>
                        <textarea class="field min-h-24 text-sm" id="reply-{{ $comment->id }}" name="body" maxlength="3000" required placeholder="Ответить {{ '@'.$comment->user->login }}…"></textarea>
                        <button class="button button-secondary button-sm mt-2"><span class="material-symbols-outlined text-base">save</span> Ответить</button>
                    </form>
                </details>
            @endif
            @if ($isOwner)
                <form method="POST" action="{{ route('games.comments.visibility', [$game, $comment]) }}" class="ml-auto">
                    @csrf @method('PATCH')
                    <button class="text-xs font-bold text-slate-500 transition hover:text-white">{{ $comment->hidden_at ? 'Показать' : 'Скрыть' }}</button>
                </form>
            @endif
        </div>
    </div>

    @foreach ($branch['children'] as $child)
        <div class="mt-3">
            @include('games._comment', ['branch' => $child, 'game' => $game, 'isOwner' => $isOwner, 'depth' => $depth + 1])
        </div>
    @endforeach
</article>
