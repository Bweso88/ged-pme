<div x-data x-on:trigger-download.window="window.location = $event.detail.url" class="max-w-4xl space-y-6">
    <div class="flex items-start justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-900">{{ $document->nom }}</h2>
            <p class="text-sm text-slate-500">
                Statut : <span class="font-medium">{{ $document->statut }}</span>
                · Confidentialité : {{ $document->confidentialite }}
                · Auteur : {{ $document->auteur->name }}
            </p>
        </div>
        <div class="flex gap-2">
            <button wire:click="download" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Télécharger</button>
            @if ($document->statut === 'brouillon')
                <button wire:click="submitWorkflow" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white hover:bg-slate-700">Soumettre au workflow</button>
            @endif
            @if (in_array($document->statut, ['publie']))
                <button wire:click="$set('showArchiveForm', true)" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Archiver</button>
            @endif
            <button wire:click="moveToTrash" wire:confirm="Mettre ce document à la corbeille ?" class="rounded-md bg-white ring-1 ring-red-300 text-red-600 px-3 py-1.5 text-sm hover:bg-red-50">Supprimer</button>
        </div>
    </div>

    @if ($showArchiveForm)
        <div class="rounded-lg border border-slate-200 bg-white p-4 space-y-3">
            <h3 class="font-medium text-slate-800">Archiver le document</h3>
            <div>
                <label class="block text-sm text-slate-600">Confidentialité</label>
                <select wire:model="archiveConfidentialite" class="mt-1 rounded-md border-slate-300 text-sm">
                    <option value="interne">Interne</option>
                    <option value="restreint">Restreint</option>
                    <option value="confidentiel">Confidentiel</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-slate-600">Motif d'archivage</label>
                <input wire:model="archiveMotif" type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm">
            </div>
            <button wire:click="archive" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Confirmer l'archivage</button>
        </div>
    @endif

    {{-- Workflow --}}
    @if ($workflowInstance && $workflowInstance->statut === 'en_cours')
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <p class="text-sm font-medium text-amber-800">En attente de validation : {{ $workflowInstance->etapeCourante->nom }}</p>

            @can('validateWorkflow', $document)
                <div class="mt-3 space-y-2">
                    <textarea wire:model="decisionComment" rows="2" placeholder="Commentaire (obligatoire pour un rejet ou une demande de modification)"
                              class="w-full rounded-md border-slate-300 text-sm"></textarea>
                    <div class="flex gap-2">
                        <button wire:click="approveWorkflow" class="rounded-md bg-emerald-600 px-3 py-1.5 text-sm text-white">Approuver</button>
                        <button wire:click="rejectWorkflow" class="rounded-md bg-red-600 px-3 py-1.5 text-sm text-white">Rejeter</button>
                        <button wire:click="requestChangesWorkflow" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm">Demander une modification</button>
                    </div>
                </div>
            @endcan
        </div>
    @endif

    {{-- Métadonnées --}}
    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <h3 class="font-medium text-slate-800 mb-3">Métadonnées</h3>
        <div class="grid grid-cols-2 gap-4">
            @foreach ($metadataFields as $field)
                <div>
                    <label class="block text-sm text-slate-600">{{ $field->label }} @if($field->obligatoire) * @endif</label>
                    <input wire:model="metadata.{{ $field->code }}" type="text" class="mt-1 w-full rounded-md border-slate-300 text-sm">
                    @error("metadata.{$field->code}") <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            @endforeach
        </div>
        <button wire:click="saveMetadata" class="mt-3 rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Enregistrer</button>
    </div>

    {{-- Versions --}}
    <div class="rounded-lg border border-slate-200 bg-white p-4">
        <h3 class="font-medium text-slate-800 mb-3">Versions</h3>
        <ul class="divide-y divide-slate-100 text-sm mb-3">
            @foreach ($document->versions as $version)
                <li class="py-2 flex items-center justify-between">
                    <span>
                        v{{ $version->numero_version }} — {{ $version->auteur->name }} — {{ $version->created_at->format('d/m/Y H:i') }}
                        @if ($version->id === $document->version_courante_id) <span class="text-emerald-600">(courante)</span> @endif
                    </span>
                    @if ($version->id !== $document->version_courante_id)
                        <button wire:click="restoreVersion('{{ $version->id }}')" class="text-slate-500 hover:text-slate-900">Restaurer</button>
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="flex items-center gap-2">
            <input type="file" wire:model="newVersion" class="text-sm">
            <input wire:model="newVersionComment" type="text" placeholder="Commentaire" class="rounded-md border-slate-300 text-sm">
            <button wire:click="uploadNewVersion" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Ajouter une version</button>
        </div>
    </div>

    {{-- Partage --}}
    @can('share', $document)
        <div class="rounded-lg border border-slate-200 bg-white p-4 space-y-4">
            <h3 class="font-medium text-slate-800">Partage</h3>

            <ul class="text-sm divide-y divide-slate-100">
                @foreach ($document->shares as $share)
                    <li class="py-2 flex items-center justify-between">
                        <span>
                            {{ ucfirst($share->type) }}
                            @if ($share->cibleUser) — {{ $share->cibleUser->name }} @endif
                            @if ($share->expire_at) — expire le {{ $share->expire_at->format('d/m/Y H:i') }} @endif
                            @if ($share->revoque) <span class="text-red-500">(révoqué)</span> @endif
                        </span>
                        @if (! $share->revoque)
                            <button wire:click="revokeShare('{{ $share->id }}')" class="text-red-600 hover:underline">Révoquer</button>
                        @endif
                    </li>
                @endforeach
            </ul>

            <div class="flex gap-2">
                <input wire:model="shareEmail" type="email" placeholder="E-mail d'un collaborateur" class="rounded-md border-slate-300 text-sm">
                <button wire:click="shareByEmail" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm">Partager</button>
            </div>

            <div class="flex gap-2 items-end">
                <div>
                    <label class="block text-xs text-slate-500">Expiration du lien</label>
                    <input wire:model="shareExpiresAt" type="datetime-local" class="rounded-md border-slate-300 text-sm">
                </div>
                <div>
                    <label class="block text-xs text-slate-500">Mot de passe (optionnel)</label>
                    <input wire:model="sharePassword" type="text" class="rounded-md border-slate-300 text-sm">
                </div>
                <button wire:click="createShareLink" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm">Créer un lien</button>
            </div>
        </div>
    @endcan
</div>
