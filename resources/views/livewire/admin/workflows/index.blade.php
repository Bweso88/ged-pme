<div class="space-y-6">
    <form wire:submit="createWorkflow" class="grid grid-cols-3 gap-3 rounded-lg border border-slate-200 bg-white p-4">
        <input wire:model="nom" type="text" placeholder="Nom du workflow" class="rounded-md border-slate-300 text-sm">
        <select wire:model="documentTypeId" class="rounded-md border-slate-300 text-sm">
            <option value="">Tous types de documents</option>
            @foreach ($documentTypes as $type)
                <option value="{{ $type->id }}">{{ $type->nom }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Créer</button>
    </form>

    @error('delete') <p class="text-sm text-red-600 bg-red-50 rounded-md px-3 py-2">{{ $message }}</p> @enderror

    <div class="space-y-4">
        @foreach ($workflows as $workflow)
            <div class="rounded-lg border border-slate-200 bg-white p-4 {{ ! $workflow->actif ? 'opacity-60' : '' }}">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-medium text-slate-900">
                            {{ $workflow->nom }}
                            @unless ($workflow->actif) <span class="text-xs text-slate-400">(désactivé)</span> @endunless
                        </p>
                        <p class="text-xs text-slate-500">Type : {{ $workflow->documentType?->nom ?? 'Tous' }}</p>
                    </div>
                    <div class="flex gap-3 text-sm">
                        <button wire:click="manageSteps('{{ $workflow->id }}')" class="text-slate-600 hover:underline">Étapes</button>
                        <button wire:click="startRename('{{ $workflow->id }}')" class="text-slate-600 hover:underline">Renommer</button>
                        <button wire:click="toggleActive('{{ $workflow->id }}')" class="text-slate-600 hover:underline">
                            {{ $workflow->actif ? 'Désactiver' : 'Réactiver' }}
                        </button>
                        <button wire:click="deleteWorkflow('{{ $workflow->id }}')" wire:confirm="Supprimer définitivement « {{ $workflow->nom }} » ?" class="text-red-600 hover:underline">Supprimer</button>
                    </div>
                </div>

                @if ($renamingWorkflowId === $workflow->id)
                    <form wire:submit="updateWorkflow" class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3">
                        <input wire:model="renameNom" type="text" placeholder="Nom du workflow" class="rounded-md border-slate-300 text-sm">
                        <select wire:model="renameDocumentTypeId" class="rounded-md border-slate-300 text-sm">
                            <option value="">Tous types de documents</option>
                            @foreach ($documentTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->nom }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm">Enregistrer</button>
                    </form>
                    @error('renameNom') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @endif

                <ol class="mt-2 text-sm text-slate-600 list-decimal list-inside">
                    @foreach ($workflow->steps as $step)
                        <li class="flex items-center justify-between">
                            <span>{{ $step->nom }} — {{ $step->roleRequis?->nom ?? $step->userRequis?->name }}</span>
                            @if ($editingWorkflowId === $workflow->id)
                                <button wire:click="removeStep('{{ $step->id }}')" wire:confirm="Supprimer cette étape ?" class="text-xs text-red-600 hover:underline">Retirer</button>
                            @endif
                        </li>
                    @endforeach
                </ol>

                @if ($editingWorkflowId === $workflow->id)
                    <form wire:submit="addStep" class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3">
                        <input wire:model="stepNom" type="text" placeholder="Nom de l'étape" class="rounded-md border-slate-300 text-sm">
                        <select wire:model="stepRoleId" class="rounded-md border-slate-300 text-sm">
                            <option value="">Rôle requis…</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->id }}">{{ $role->nom }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm">Ajouter l'étape</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</div>
