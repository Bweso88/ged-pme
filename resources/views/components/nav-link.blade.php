@props(['href', 'active' => false])
<a href="{{ $href }}"
   {{ $attributes->merge(['class' => 'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition '
        .($active ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/30' : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900')]) }}>
    @isset($icon)
        <span class="shrink-0 {{ $active ? 'text-white' : 'text-slate-400' }}">{{ $icon }}</span>
    @endisset
    <span class="truncate">{{ $slot }}</span>
</a>
