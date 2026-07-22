@extends('layouts.app')

@section('title', 'Настройка аватара')

@section('content')
<div class="mx-auto max-w-5xl" data-avatar-editor>
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="eyebrow"><span class="material-symbols-outlined">face</span> Профиль</span>
            <h1 class="page-title">Настройка аватара</h1>
            <p class="muted mt-2">Выберите фотографию, приблизьте её и поместите лицо в центр круга.</p>
        </div>
        <a href="{{ route('settings.edit') }}" class="button button-secondary shrink-0"><span class="material-symbols-outlined">arrow_back</span> К настройкам</a>
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ route('settings.avatar') }}" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_18rem]" data-avatar-form>
        @csrf @method('PATCH')

        <section class="panel min-w-0">
            <div>
                <label class="label" for="avatar">Фотография</label>
                <input
                    class="field file:mr-3 file:rounded-lg file:border-0 file:bg-violet-500/15 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-violet-300"
                    id="avatar"
                    name="avatar"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    required
                    data-avatar-input
                >
                <p class="mt-2 text-[11px] leading-5 text-slate-500">JPEG, PNG, WebP или GIF, не более 8 МБ. Итоговый файл будет сохранён в WebP 256×256.</p>
                @error('avatar') <p class="field-error">{{ $message }}</p> @enderror
                <p class="field-error hidden" data-avatar-error></p>
            </div>

            <div class="mt-5 grid min-h-80 place-items-center overflow-hidden rounded-2xl border border-white/10 bg-black/25" data-avatar-workspace>
                <div class="px-6 py-16 text-center" data-avatar-placeholder>
                    <span class="material-symbols-outlined text-6xl text-violet-300/25">add_photo_alternate</span>
                    <p class="mt-3 text-sm font-bold text-slate-300">Сначала выберите фотографию</p>
                    <p class="mt-1 text-xs text-slate-600">Редактор откроется прямо здесь.</p>
                </div>
                <div class="hidden h-full w-full" data-avatar-cropper></div>
            </div>

            <div class="mt-4 hidden flex-wrap gap-2" data-avatar-controls aria-label="Инструменты кадрирования">
                <button type="button" class="button button-secondary button-sm" data-avatar-action="zoom-out" title="Отдалить"><span class="material-symbols-outlined">zoom_out</span></button>
                <button type="button" class="button button-secondary button-sm" data-avatar-action="zoom-in" title="Приблизить"><span class="material-symbols-outlined">zoom_in</span></button>
                <button type="button" class="button button-secondary button-sm" data-avatar-action="rotate-left" title="Повернуть влево"><span class="material-symbols-outlined">rotate_left</span></button>
                <button type="button" class="button button-secondary button-sm" data-avatar-action="rotate-right" title="Повернуть вправо"><span class="material-symbols-outlined">rotate_right</span></button>
                <button type="button" class="button button-secondary button-sm ml-auto" data-avatar-action="reset"><span class="material-symbols-outlined">restart_alt</span> Сбросить</button>
            </div>
        </section>

        <aside class="panel h-fit lg:sticky lg:top-24">
            <h2 class="text-lg font-extrabold">Предпросмотр</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500">Так аватар будет выглядеть в профиле.</p>

            <div class="relative mx-auto mt-6 grid size-40 place-items-center overflow-hidden rounded-full border-4 border-[#0a0c16] bg-gradient-to-br from-violet-900 to-cyan-950 shadow-xl ring-4 ring-violet-400/30" data-avatar-preview>
                @if (auth()->user()->avatar_url)
                    <img src="{{ auth()->user()->avatar_url }}" alt="Текущий аватар" class="absolute inset-0 h-full w-full object-cover" data-avatar-current>
                @else
                    <span class="material-symbols-outlined text-6xl text-white/30" data-avatar-current>person</span>
                @endif
                <cropper-viewer class="absolute inset-0 h-full w-full" selection="#avatar-selection" resize="none" data-avatar-viewer hidden></cropper-viewer>
            </div>

            <div class="mt-6 rounded-2xl border border-white/8 bg-black/20 p-4 text-xs leading-5 text-slate-400">
                <p class="font-bold text-slate-200">Небольшая подсказка</p>
                <p class="mt-1">Оставьте немного пространства вокруг лица: круглая рамка слегка скрывает углы квадратного изображения.</p>
            </div>

            <button class="button button-primary mt-5 w-full" disabled data-avatar-submit>
                <span class="material-symbols-outlined">check</span>
                <span data-avatar-submit-label>Сохранить аватар</span>
            </button>
        </aside>
    </form>
</div>
@endsection
