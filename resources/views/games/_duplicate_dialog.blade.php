@php($duplicateGame = $duplicateGame ?? null)
@php($ajax = $ajax ?? false)

@if ($duplicateGame || $ajax)
    <div class="fixed inset-0 z-[80] {{ $duplicateGame ? 'flex' : 'hidden' }} items-center justify-center p-4" role="presentation" data-game-duplicate-dialog @if ($ajax) data-ajax @endif>
        <button type="button" class="absolute inset-0 cursor-pointer bg-black/80 backdrop-blur-sm" aria-label="Остаться на странице" data-game-duplicate-close></button>
        <section class="glass relative z-10 w-full max-w-lg overflow-hidden rounded-3xl border border-amber-300/20 bg-[#0b0e1a] shadow-2xl shadow-black/70" role="dialog" aria-modal="true" aria-labelledby="game-duplicate-title">
            <header class="flex items-start gap-3 border-b border-white/10 p-5 sm:p-6">
                <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-amber-500/10 text-amber-300">
                    <span class="material-symbols-outlined">content_copy</span>
                </span>
                <div class="min-w-0 flex-1">
                    <h2 id="game-duplicate-title" class="text-lg font-extrabold text-white">Такая игра уже добавлена</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-400">
                        Игра «<span class="font-bold text-slate-200" data-game-duplicate-title>{{ $duplicateGame['title'] ?? '' }}</span>» уже есть в списке «<span class="font-bold text-slate-200" data-game-duplicate-list>{{ $duplicateGame['list'] ?? '' }}</span>».
                    </p>
                </div>
                <button type="button" class="grid size-9 shrink-0 cursor-pointer place-items-center rounded-xl text-slate-500 transition hover:bg-white/8 hover:text-white" aria-label="Закрыть" data-game-duplicate-close>
                    <span class="material-symbols-outlined">close</span>
                </button>
            </header>

            <div class="space-y-3 p-5 sm:p-6">
                <a href="{{ $duplicateGame['edit_url'] ?? '#' }}" class="button button-primary w-full justify-center" data-game-duplicate-edit>
                    <span class="material-symbols-outlined">edit</span> Перейти к редактированию добавленной игры
                </a>
                <button type="button" class="button button-secondary w-full justify-center" data-game-duplicate-close>
                    <span class="material-symbols-outlined">arrow_back</span> Остаться и изменить данные
                </button>
                @if ($ajax)
                    <button type="button" class="button w-full justify-center border-amber-300/25 bg-amber-500/10 text-amber-200 hover:border-amber-200/50 hover:bg-amber-500/15" data-game-duplicate-allow>
                        <span class="material-symbols-outlined">add_circle</span> Всё равно сохранить дубликат
                    </button>
                @else
                    <button type="submit" form="{{ $formId }}" name="allow_duplicate" value="1" class="button w-full justify-center border-amber-300/25 bg-amber-500/10 text-amber-200 hover:border-amber-200/50 hover:bg-amber-500/15" data-game-duplicate-allow>
                        <span class="material-symbols-outlined">add_circle</span> Всё равно сохранить дубликат
                    </button>
                @endif
            </div>
        </section>
    </div>
@endif
