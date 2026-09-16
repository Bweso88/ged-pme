<div>
    <select wire:model.live="action" class="mb-4 rounded-md border-slate-300 text-sm">
        <option value="">Toutes les actions</option>
        @foreach (['connexion','deconnexion','creation','consultation','telechargement','modification','deplacement','partage','suppression','restauration','validation','archivage','changement_permission'] as $a)
            <option value="{{ $a }}">{{ $a }}</option>
        @endforeach
    </select>

    <table class="w-full text-sm bg-white rounded-lg ring-1 ring-slate-200 overflow-hidden">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-4 py-2">Date</th>
                <th class="px-4 py-2">Utilisateur</th>
                <th class="px-4 py-2">Action</th>
                <th class="px-4 py-2">Ressource</th>
                <th class="px-4 py-2">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($logs as $log)
                <tr>
                    <td class="px-4 py-2 text-slate-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                    <td class="px-4 py-2">{{ $log->utilisateur?->name ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $log->action }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $log->ressource_type }}</td>
                    <td class="px-4 py-2 text-slate-500">{{ $log->ip_adresse }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-4">{{ $logs->links() }}</div>
</div>
