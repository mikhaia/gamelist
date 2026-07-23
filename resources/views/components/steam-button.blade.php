@props(['label' => 'Войти через'])

<a {{ $attributes->class('group mx-auto flex h-14 w-[280px] max-w-full cursor-pointer items-center justify-center gap-3 rounded-2xl border border-[#66c0f4]/25 bg-gradient-to-b from-[#28394d] to-[#171a21] px-5 shadow-lg shadow-black/25 transition hover:-translate-y-0.5 hover:border-[#66c0f4]/55 hover:from-[#31465e] hover:to-[#1b2838]') }}>
    <span class="shrink-0 text-xs font-bold text-slate-300 transition group-hover:text-white">{{ $label }}</span>
    <img src="{{ asset('images/steam/sign-in.svg') }}" width="120" height="37" alt="Steam" class="h-auto w-[120px] shrink-0">
</a>
