<div>
    <nav class="mb-4 text-sm text-slate-500 flex items-center gap-1">
        <a href="{{ route('documents.index') }}" wire:navigate class="hover:text-slate-900">Entreprise</a>
        @foreach ($breadcrumb as $crumb)
            <span>/</span>
            <a href="{{ route('documents.show-folder', $crumb) }}" wire:navigate class="hover:text-slate-900">{{ $crumb->nom }}</a>
        @endforeach
    </nav>

    <div class="flex items-center justify-between mb-4">
        <div class="flex gap-2">
            <button wire:click="$set('showNewFolder', true)" class="rounded-md bg-white ring-1 ring-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50">
                + Nouveau dossier
            </button>
        </div>

        @if ($folder)
            <div>
                <label class="inline-flex items-center gap-2 rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white hover:bg-slate-700 cursor-pointer">
                    <span>Importer des documents</span>
                    <input type="file" wire:model="uploads" multiple class="hidden">
                </label>
            </div>
        @endif
    </div>

    @if ($showNewFolder)
        <form wire:submit="createFolder" class="mb-4 flex items-center gap-2">
            <input wire:model="newFolderName" type="text" placeholder="Nom du dossier"
                   class="rounded-md border-slate-300 text-sm shadow-sm focus:border-slate-500 focus:ring-slate-500">
            <button type="submit" class="rounded-md bg-slate-900 px-3 py-1.5 text-sm text-white">Créer</button>
            <button type="button" wire:click="$set('showNewFolder', false)" class="text-sm text-slate-500">Annuler</button>
        </form>
        @error('newFolderName') <p class="text-sm text-red-600 mb-2">{{ $message }}</p> @enderror
    @endif

    @error('uploads') <p class="text-sm text-red-600 mb-2">{{ $message }}</p> @enderror

    <div wire:loading wire:target="uploads" class="mb-4 text-sm text-slate-500">Import en cours…</div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-6">
        @foreach ($subfolders as $sub)
            <a href="{{ route('documents.show-folder', $sub) }}" wire:navigate
               class="rounded-lg border border-slate-200 bg-white p-4 hover:shadow-sm flex items-center gap-3">
                <span class="text-xl">📁</span>
                <span class="font-medium text-slate-800">{{ $sub->nom }}</span>
            </a>
        @endforeach
    </div>

    @if ($folder)
        <table class="w-full text-sm bg-white rounded-lg ring-1 ring-slate-200 overflow-hidden">
            <thead class="bg-slate-50 text-slate-500 text-left">
                <tr>
                    <th class="px-4 py-2">Nom</th>
                    <th class="px-4 py-2">Statut</th>
                    <th class="px-4 py-2">Modifié le</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($documents as $document)
                    <tr>
                        <td class="px-4 py-2">
                            <a href="{{ route('documents.show', $document) }}" wire:navigate class="text-slate-900 font-medium hover:underline">
                                {{ $document->nom }}
                            </a>
                        </td>
                        <td class="px-4 py-2 text-slate-500">{{ $document->statut }}</td>
                        <td class="px-4 py-2 text-slate-500">{{ $document->updated_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('documents.show', $document) }}" wire:navigate class="text-slate-500 hover:text-slate-900">Ouvrir</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-center text-slate-400">Aucun document dans ce dossier.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif
</div>
