<div class="space-y-6">
    <form wire:submit="save" class="grid grid-cols-5 gap-3 rounded-lg border border-slate-200 bg-white p-4">
        <select wire:model="documentTypeId" class="rounded-md border-slate-300 text-sm">
            <option value="">Type de document…</option>
            @foreach ($documentTypes as $type)
                <option value="{{ $type->id }}">{{ $type->nom }}</option>
            @endforeach
        </select>
        <input wire:model="dureeConservationMois" type="number" placeholder="Durée (mois)" class="rounded-md border-slate-300 text-sm">
        <select wire:model="actionAExpiration" class="rounded-md border-slate-300 text-sm">
            <option value="conserver">Conserver</option>
            <option value="proposer_destruction">Proposer pour destruction</option>
            <option value="transferer_archive">Transférer vers une archive</option>
            <option value="demander_validation">Demander une validation</option>
        </select>
        <input wire:model="categorieArchive" type="text" placeholder="Catégorie d'archive" class="rounded-md border-slate-300 text-sm">
        <button type="submit" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Enregistrer</button>
    </form>

    <table class="w-full text-sm bg-white rounded-lg ring-1 ring-slate-200 overflow-hidden">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-4 py-2">Type</th>
                <th class="px-4 py-2">Durée</th>
                <th class="px-4 py-2">Action à expiration</th>
                <th class="px-4 py-2">Catégorie</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($policies as $policy)
                <tr>
                    <td class="px-4 py-2">{{ $policy->documentType->nom }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $policy->duree_conservation_mois }} mois</td>
                    <td class="px-4 py-2 text-slate-500">{{ $policy->action_a_expiration }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $policy->categorie_archive }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
