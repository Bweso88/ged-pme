<div class="space-y-6">
    <form wire:submit="createType" class="grid grid-cols-4 gap-3 rounded-lg border border-slate-200 bg-white p-4">
        <input wire:model="nom" type="text" placeholder="Nom (ex : Contrat)" class="rounded-md border-slate-300 text-sm">
        <input wire:model="code" type="text" placeholder="Code (ex : contrat)" class="rounded-md border-slate-300 text-sm">
        <input wire:model="dureeConservationMois" type="number" placeholder="Conservation (mois)" class="rounded-md border-slate-300 text-sm">
        <button type="submit" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Créer le type</button>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach ($types as $type)
            <div class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900">{{ $type->nom }}</p>
                        <p class="text-xs text-slate-500">Code : {{ $type->code }} · Conservation : {{ $type->duree_conservation_mois ?? '—' }} mois</p>
                    </div>
                    <button wire:click="manageFields('{{ $type->id }}')" class="text-sm text-slate-600 hover:underline">Métadonnées</button>
                </div>

                @if ($editingTypeId === $type->id)
                    <div class="mt-3 border-t border-slate-100 pt-3">
                        <ul class="text-sm mb-2 divide-y divide-slate-100">
                            @foreach ($editingFields as $field)
                                <li class="py-1">{{ $field->label }} ({{ $field->type }}) @if($field->obligatoire) <span class="text-red-500">*</span> @endif</li>
                            @endforeach
                        </ul>
                        <form wire:submit="addField" class="grid grid-cols-4 gap-2">
                            <input wire:model="fieldLabel" type="text" placeholder="Libellé" class="rounded-md border-slate-300 text-sm">
                            <input wire:model="fieldCode" type="text" placeholder="Code" class="rounded-md border-slate-300 text-sm">
                            <select wire:model="fieldType" class="rounded-md border-slate-300 text-sm">
                                <option value="texte">Texte</option>
                                <option value="nombre">Nombre</option>
                                <option value="date">Date</option>
                                <option value="liste">Liste</option>
                                <option value="booleen">Booléen</option>
                            </select>
                            <button type="submit" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm">Ajouter</button>
                        </form>
                        <label class="mt-2 flex items-center gap-2 text-xs text-slate-500">
                            <input wire:model="fieldObligatoire" type="checkbox"> Champ obligatoire
                        </label>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
