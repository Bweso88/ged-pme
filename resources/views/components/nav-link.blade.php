@props(['href', 'active' => false])
<a href="{{ $href }}"
   {{ $attributes->merge(['class' => 'block rounded-md px-3 py-2 '.($active ? 'bg-slate-800 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white')]) }}>
    {{ $slot }}
</a>
