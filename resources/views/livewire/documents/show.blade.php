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
            @if ($canEditOnline)
                <a href="{{ route('documents.edit-online', $document) }}" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Éditer en ligne</a>
            @endif
            @if ($document->statut === 'brouillon')
                <button wire:click="submitWorkflow" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white hover:bg-slate-700">Soumettre au workflow</button>
            @endif
            @if (in_array($document->statut, ['publie']))
                <button wire:click="$set('showArchiveForm', true)" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Archiver</button>
            @endif
            <button wire:click="moveToTrash" wire:confirm="Mettre ce document à la corbeille ?" class="rounded-md bg-white ring-1 ring-red-300 text-red-600 px-3 py-1.5 text-sm hover:bg-red-50">Supprimer</button>
        </div>
    </div>

    @error('workflow') <p class="text-sm text-red-600 bg-red-50 rounded-md px-3 py-2">{{ $message }}</p> @enderror

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

    {{-- Suggestions d'extraction automatique --}}
    @if ($metadataSuggestions->isNotEmpty())
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
            <h3 class="font-medium text-slate-800 mb-1">Détecté automatiquement dans le document</h3>
            <p class="text-xs text-slate-500 mb-3">Extrait du contenu OCR par des règles configurées sur le type de document — à valider avant d'être enregistré.</p>
            <ul class="space-y-2">
                @foreach ($metadataSuggestions as $suggestion)
                    <li class="flex items-center justify-between rounded-md bg-white border border-amber-100 px-3 py-2 text-sm">
                        <span><span class="text-slate-500">{{ $suggestion->metadataField->label }} :</span> <span class="font-medium text-slate-900">{{ $suggestion->valeur_proposee }}</span></span>
                        <span class="flex gap-2">
                            <button wire:click="acceptSuggestion('{{ $suggestion->id }}')" class="rounded-md bg-slate-900 px-2 py-1 text-xs text-white">Accepter</button>
                            <button wire:click="rejectSuggestion('{{ $suggestion->id }}')" class="rounded-md ring-1 ring-slate-300 px-2 py-1 text-xs">Ignorer</button>
                        </span>
                    </li>
                @endforeach
            </ul>
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

        @if ($document->versions->count() > 1)
            <div class="mt-4 border-t border-slate-100 pt-3">
                <p class="text-xs text-slate-500 mb-2">Comparer deux versions (basé sur le texte extrait par OCR)</p>
                <div class="flex items-center gap-2">
                    <select wire:model="compareFromVersionId" class="rounded-md border-slate-300 text-sm">
                        <option value="">Version...</option>
                        @foreach ($document->versions as $version)
                            <option value="{{ $version->id }}">v{{ $version->numero_version }}</option>
                        @endforeach
                    </select>
                    <span class="text-slate-400">→</span>
                    <select wire:model="compareToVersionId" class="rounded-md border-slate-300 text-sm">
                        <option value="">Version...</option>
                        @foreach ($document->versions as $version)
                            <option value="{{ $version->id }}">v{{ $version->numero_version }}</option>
                        @endforeach
                    </select>
                    <button wire:click="compareVersions" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">Comparer</button>
                </div>
                @error('compareFromVersionId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('compareToVersionId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                @if ($diffUnavailable)
                    <p class="mt-2 text-xs text-slate-500">Aucun texte extrait (OCR) disponible sur l'une de ces versions : la comparaison n'est pas possible.</p>
                @endif

                @if ($diffOutput !== null)
                    <pre class="mt-3 max-h-96 overflow-auto rounded-md bg-slate-950 p-3 text-xs leading-5">@foreach (explode("\n", $diffOutput) as $line)<span class="block {{ str_starts_with($line, '+') && ! str_starts_with($line, '+++') ? 'text-emerald-400' : (str_starts_with($line, '-') && ! str_starts_with($line, '---') ? 'text-red-400' : 'text-slate-400') }}">{{ $line }}</span>
@endforeach</pre>
                @endif
            </div>
        @endif
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

    {{-- Signature électronique --}}
    @can('sign', $document)
        <div class="rounded-lg border border-slate-200 bg-white p-4 space-y-4">
            <h3 class="font-medium text-slate-800">Signature électronique</h3>
            <p class="text-xs text-slate-500">
                Distinct de la validation de workflow : ceci déclenche une signature électronique
                juridiquement opposable via un prestataire tiers.
            </p>

            @if (! $signatureConfigured)
                <p class="text-sm text-amber-600 bg-amber-50 rounded-md px-3 py-2">
                    Aucun prestataire de signature électronique n'est configuré. Contactez votre administrateur.
                </p>
            @endif

            @if ($signatureRequests->isNotEmpty())
                <ul class="text-sm divide-y divide-slate-100">
                    @foreach ($signatureRequests as $request)
                        <li class="py-2 flex items-center justify-between">
                            <span>
                                {{ collect($request->signataires)->pluck('email')->join(', ') }}
                            </span>
                            <span class="text-xs rounded-full px-2 py-0.5 {{ $request->statut === 'signe' ? 'bg-emerald-100 text-emerald-700' : ($request->statut === 'erreur' ? 'bg-red-100 text-red-700' : 'bg-slate-100 text-slate-600') }}">
                                {{ $request->statut }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($signatureConfigured)
                <ul class="text-sm divide-y divide-slate-100">
                    @foreach ($pendingSignataires as $index => $signataire)
                        <li class="py-1 flex items-center justify-between">
                            <span>{{ $signataire['nom'] }} — {{ $signataire['email'] }}</span>
                            <button wire:click="removeSignataire({{ $index }})" class="text-red-600 hover:underline text-xs">Retirer</button>
                        </li>
                    @endforeach
                </ul>

                <div class="flex gap-2">
                    <input wire:model="signataireNom" type="text" placeholder="Nom du signataire" class="rounded-md border-slate-300 text-sm">
                    <input wire:model="signataireEmail" type="email" placeholder="E-mail" class="rounded-md border-slate-300 text-sm">
                    <button wire:click="addSignataire" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm">Ajouter</button>
                </div>
                @error('signataireNom') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @error('signataireEmail') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @error('pendingSignataires') <p class="text-xs text-red-600">{{ $message }}</p> @enderror

                <button wire:click="requestSignature" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Envoyer en signature</button>
            @endif
        </div>
    @endcan
</div>
