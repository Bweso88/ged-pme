<div class="relative">
    <button wire:click="toggle" class="relative inline-flex items-center rounded-md p-2 text-slate-500 hover:bg-slate-100">
        <span>Notifications</span>
        @if ($unreadCount > 0)
            <span class="ml-2 inline-flex items-center justify-center rounded-full bg-red-600 px-2 py-0.5 text-xs font-medium text-white">
                {{ $unreadCount }}
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
