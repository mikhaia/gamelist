@foreach ($results as $result)
    @php($payload = json_encode([
        'title' => $result['title'], 'hltb_id' => $result['id'],
        'catalog_cover_url' => $result['cover_url'], 'source_cover_url' => $result['cover_url'],
        'main_story_minutes' => $result['main_story_minutes'], 'main_extra_minutes' => $result['main_extra_minutes'] ?? null,
        'completionist_minutes' => $result['completionist_minutes'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
    @php($catalogKey = $result['id'] ? 'hltb-'.$result['id'] : 'title-'.md5($result['title']))
    <label data-catalog-key="{{ $catalogKey }}" class="flex cursor-pointer gap-3 rounded-2xl border border-white/8 bg-black/15 p-3 transition has-checked:border-violet-400/50 has-checked:bg-violet-500/8 hover:bg-white/5">
        <input type="radio" name="catalog_result" class="mt-1 accent-violet-500" data-catalog-result="{{ $payload }}">
        <div class="grid h-20 w-15 shrink-0 place-items-center overflow-hidden rounded-xl bg-white/5">
            @if ($result['cover_url'])<img src="{{ $result['cover_url'] }}" alt="" class="h-full w-full object-cover" loading="lazy">@else<span class="material-symbols-outlined text-slate-700">image</span>@endif
        </div>
        <span class="min-w-0 flex-1">
            <span class="flex items-start justify-between gap-2">
                <strong class="line-clamp-2 text-sm leading-5">{{ $result['title'] }}</strong>
                @if ($cached ?? false)<span class="shrink-0 rounded-full bg-cyan-500/10 px-2 py-1 text-[9px] font-bold uppercase tracking-wider text-cyan-300">Из кэша</span>@endif
            </span>
            <span class="mt-2 flex flex-wrap gap-2 text-[10px] text-slate-500">
                @if ($result['main_story_minutes'])<span>Сюжет: {{ round($result['main_story_minutes'] / 60) }} ч</span>@endif
                @if ($result['completionist_minutes'])<span>100%: {{ round($result['completionist_minutes'] / 60) }} ч</span>@endif
            </span>
        </span>
    </label>
@endforeach
