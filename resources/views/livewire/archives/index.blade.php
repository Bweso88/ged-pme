<div class="space-y-8">
    <div>
        <h2 class="font-medium text-slate-800 mb-3">Documents archivés</h2>
        <table class="w-full text-sm bg-white rounded-lg ring-1 ring-slate-200 overflow-hidden">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2">Document</th>
                    <th class="px-4 py-2">Catégorie</th>
                    <th class="px-4 py-2">Confidentialité</th>
                    <th class="px-4 py-2">Destruction prévue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($active as $record)
                    <tr>
                        <td class="px-4 py-2"><a href="{{ route('documents.show', $record->document) }}" wire:navigate class="hover:underline">{{ $record->document->nom }}</a></td>
                        <td class="px-4 py-2 text-slate-500">{{ $record->categorie }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $record->confidentialite }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $record->date_destruction_prevue?->format('d/m/Y') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-400">Aucun document archivé.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        <h2 class="font-medium text-slate-800 mb-3">Propositions de destruction</h2>
        <div class="space-y-3">
            @forelse ($proposed as $record)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <p class="font-medium text-amber-900">{{ $record->document->nom }}</p>
                    <p class="text-sm text-amber-700">Échéance atteinte le {{ $record->date_destruction_prevue?->format('d/m/Y') }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <input wire:model="motif" type="text" placeholder="Motif de la décision" class="rounded-md border-slate-300 text-sm">
                        <button wire:click="validateDestruction('{{ $record->id }}')" wire:confirm="Valider la destruction définitive ?" class="rounded-md bg-red-600 px-3 py-1.5 text-sm text-white">Valider la destruction</button>
                        <button wire:click="extend('{{ $record->id }}')" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm">Prolonger 12 mois</button>
                    </div>
                    @error('motif') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            @empty
                <p class="text-sm text-slate-400">Aucune proposition en attente.</p>
            @endforelse
        </div>
    </div>
</div>
