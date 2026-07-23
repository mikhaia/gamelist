@extends('layouts.admin')

@section('title', 'Файлы')

@section('content')
<nav class="admin-panel mb-5 grid gap-2 p-2 sm:grid-cols-2 xl:grid-cols-4" aria-label="Категории файлов">
    @foreach ($types as $key => $metadata)
        <a href="{{ route('admin.files.index', ['type' => $key]) }}" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-extrabold transition {{ $type === $key ? 'bg-slate-950 text-white shadow-lg shadow-slate-900/15' : 'text-slate-500 hover:bg-white/70 hover:text-slate-900' }}">
            <span class="material-symbols-outlined">{{ $metadata['icon'] }}</span>
            {{ $metadata['label'] }}
        </a>
    @endforeach
</nav>

<section class="admin-panel overflow-hidden" aria-labelledby="files-heading">
    <div class="flex items-center justify-between gap-4 border-b border-slate-200/80 px-5 py-4 sm:px-6">
        <div>
            <p class="admin-kicker">{{ $types[$type]['directory'] }}</p>
            <h2 id="files-heading" class="mt-1 font-extrabold text-slate-950">{{ $types[$type]['label'] }}</h2>
        </div>
        <span class="rounded-xl bg-slate-950 px-3 py-1.5 text-xs font-extrabold text-white">{{ number_format($files->total(), 0, ',', ' ') }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[54rem] border-collapse text-left">
            <thead class="border-b border-slate-200/80 bg-white/45 text-xs font-extrabold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-4 sm:px-6">Файл</th>
                    <th class="px-5 py-4 sm:px-6">Связан с</th>
                    <th class="px-5 py-4 sm:px-6">Владелец</th>
                    <th class="px-5 py-4 sm:px-6">Размер</th>
                    <th class="px-5 py-4 sm:px-6">Загружен</th>
                    <th class="px-5 py-4 text-right sm:px-6">Скачать</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/70">
                @forelse ($files as $file)
                    <tr class="transition hover:bg-white/55" data-admin-file="{{ $file['filename'] }}">
                        <td class="max-w-64 px-5 py-4 sm:px-6">
                            <span class="block truncate font-mono text-xs font-bold text-slate-700" title="{{ $file['path'] }}">{{ $file['filename'] }}</span>
                            <span class="mt-1 block truncate text-[10px] text-slate-400">{{ $file['path'] }}</span>
                        </td>
                        <td class="px-5 py-4 text-sm font-bold text-slate-800 sm:px-6">{{ $file['context'] }}</td>
                        <td class="px-5 py-4 text-sm text-slate-600 sm:px-6">{{ $file['owner'] ? '@'.$file['owner'] : '—' }}</td>
                        <td class="px-5 py-4 sm:px-6">
                            <span class="rounded-lg px-2 py-1 text-xs font-extrabold {{ $file['size'] === null ? 'bg-red-500/10 text-red-700' : 'bg-cyan-500/10 text-cyan-800' }}">{{ $file['size_formatted'] }}</span>
                        </td>
                        <td class="px-5 py-4 text-sm text-slate-500 sm:px-6">{{ $file['uploaded_at']?->format('d.m.Y H:i') ?? '—' }}</td>
                        <td class="px-5 py-4 text-right sm:px-6">
                            @if ($file['size'] !== null)
                                <a href="{{ route('admin.files.download', ['type' => $type, 'id' => $file['id']]) }}" class="inline-grid size-10 cursor-pointer place-items-center rounded-xl bg-slate-950 text-white shadow-md transition hover:-translate-y-0.5 hover:bg-violet-700" title="Скачать {{ $file['filename'] }}" aria-label="Скачать {{ $file['filename'] }}">
                                    <span class="material-symbols-outlined">download</span>
                                </a>
                            @else
                                <span class="text-xs font-bold text-red-600">Недоступен</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-16 text-center text-sm text-slate-400">В этой категории пока нет файлов.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>

@if ($files->hasPages())
    <div class="mt-5">{{ $files->links() }}</div>
@endif
@endsection
