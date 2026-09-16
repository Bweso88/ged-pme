<div>
    <div class="grid grid-cols-2 md:grid-cols-6 gap-3 mb-6 bg-white p-4 rounded-lg ring-1 ring-slate-200">
        <input wire:model.live.debounce.400ms="q" type="text" placeholder="Nom, mots-clés, contenu OCR…" class="col-span-2 rounded-md border-slate-300 text-sm">
        <select wire:model.live="documentTypeId" class="rounded-md border-slate-300 text-sm">
            <option value="">Tous les types</option>
            @foreach ($documentTypes as $type)
                <option value="{{ $type->id }}">{{ $type->nom }}</option>
            @endforeach
        </select>
        <select wire:model.live="statut" class="rounded-md border-slate-300 text-sm">
            <option value="">Tous les statuts</option>
            <option value="brouillon">Brouillon</option>
            <option value="soumis">Soumis</option>
            <option value="en_validation">En validation</option>
            <option value="publie">Publié</option>
            <option value="archive">Archivé</option>
        </select>
        <input wire:model.live="dateFrom" type="date" class="rounded-md border-slate-300 text-sm">
        <input wire:model.live="dateTo" type="date" class="rounded-md border-slate-300 text-sm">
    </div>

    <table class="w-full text-sm bg-white rounded-lg ring-1 ring-slate-200 overflow-hidden">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-4 py-2">Nom</th>
                <th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Dossier</th>
                <th class="px-4 py-2">Statut</th>
                <th class="px-4 py-2">Auteur</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($results as $document)
                <tr>
                    <td class="px-4 py-2">
                        <a href="{{ route('documents.show', $document) }}" wire:navigate class="font-medium text-slate-900 hover:underline">{{ $document->nom }}</a>
                    </td>
                    <td class="px-4 py-2 text-slate-500">{{ $document->documentType?->nom ?? '—' }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $document->folder?->nom }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $document->statut }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $document->auteur->name }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-slate-400">Aucun résultat.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="mt-4">{{ $results->links() }}</div>
</div>
