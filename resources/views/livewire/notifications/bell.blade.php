<div class="relative">
    <button wire:click="toggle" class="relative inline-flex items-center justify-center rounded-full h-10 w-10 text-slate-500 hover:bg-slate-100">
        <span class="sr-only">Notifications</span>
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
            <path d="M6 10.5a6 6 0 0 1 12 0c0 4 1.5 5.5 1.5 5.5h-15S6 14.5 6 10.5Z" /><path d="M10 19a2 2 0 0 0 4 0" />
        </svg>
        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center rounded-full bg-red-600 h-4 w-4 text-[10px] font-medium text-white">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    @if ($open)
        <div class="absolute right-0 z-10 mt-2 w-80 rounded-md bg-white shadow-lg ring-1 ring-slate-200">
            <div class="max-h-96 overflow-y-auto divide-y divide-slate-100">
                @forelse ($items as $item)
                    <button wire:click="markAsRead('{{ $item->id }}')"
                            class="block w-full text-left px-4 py-3 text-sm {{ $item->read_at ? 'text-slate-400' : 'text-slate-800 font-medium' }} hover:bg-slate-50">
                        {{ $item->data['message'] ?? $item->type }}
                        <span class="block text-xs text-slate-400">{{ $item->created_at->diffForHumans() }}</span>
                    </button>
                @empty
                    <p class="px-4 py-3 text-sm text-slate-400">Aucune notification.</p>
                @endforelse
            </div>
        </div>
    @endif
</div>
