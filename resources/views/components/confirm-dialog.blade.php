<dialog
    class="m-auto w-[min(28rem,calc(100vw-2rem))] overflow-hidden rounded-3xl border border-white/70 bg-white/90 p-0 text-slate-950 shadow-2xl shadow-black/45 backdrop-blur-2xl backdrop:bg-black/65 backdrop:backdrop-blur-sm"
    data-confirm-dialog
    aria-labelledby="confirm-dialog-title"
    aria-describedby="confirm-dialog-message"
>
    <div class="relative overflow-hidden p-6 sm:p-7">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-white"></div>
        <div class="pointer-events-none absolute -top-20 -right-20 size-52 rounded-full bg-white/80 blur-3xl"></div>

        <div class="relative flex items-start gap-4">
            <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-red-500/10 text-red-700">
                <span class="material-symbols-outlined">delete</span>
            </span>
            <div class="min-w-0">
                <h2 id="confirm-dialog-title" class="text-lg font-extrabold text-slate-950" data-confirm-title>Подтвердите удаление</h2>
                <p id="confirm-dialog-message" class="mt-2 text-sm leading-6 text-slate-600" data-confirm-message></p>
            </div>
        </div>

        <div class="relative mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="button cursor-pointer border-black/10 bg-black/5 text-slate-800 hover:bg-black/10 focus:ring-slate-400/50" data-confirm-cancel>Отмена</button>
            <button type="button" class="button cursor-pointer border-slate-950 bg-slate-950 text-white shadow-lg shadow-black/20 hover:-translate-y-0.5 hover:bg-slate-800 focus:ring-slate-500/60" data-confirm-accept>
                <span class="material-symbols-outlined">delete</span>
                <span data-confirm-accept-label>Удалить</span>
            </button>
        </div>
    </div>
</dialog>
