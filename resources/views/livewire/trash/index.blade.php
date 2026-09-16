<div>
    <table class="w-full text-sm bg-white rounded-lg ring-1 ring-slate-200 overflow-hidden">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-4 py-2">Nom</th>
                <th class="px-4 py-2">Dossier</th>
                <th class="px-4 py-2">Supprimé par</th>
                <th class="px-4 py-2">Le</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($documents as $document)
                <tr>
                    <td class="px-4 py-2 font-medium">{{ $document->nom }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $document->folder?->nom }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $document->trashedBy?->name }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $document->trashed_at?->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2 text-right space-x-3">
                        @can('restoreFromTrash', $document)
                            <button wire:click="restore('{{ $document->id }}')" class="text-slate-700 hover:underline">Restaurer</button>
                        @endcan
                        @can('forceDelete', $document)
                            <button wire:click="forceDelete('{{ $document->id }}')" wire:confirm="Suppression définitive et irréversible. Confirmer ?" class="text-red-600 hover:underline">Supprimer définitivement</button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">La corbeille est vide.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
