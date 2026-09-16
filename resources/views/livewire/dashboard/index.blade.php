<div class="space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        @foreach ([
            ['Documents', $totalDocuments],
            ['En attente de validation', $enAttenteValidation],
            ['Mes tâches', $tachesEnAttente],
            ['Archives actives', $archivesActives],
            ['Propositions de destruction', $propositionsDestruction],
            ['Échéances < 90 jours', $echeancesProches],
        ] as [$label, $value])
            <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="text-2xl font-semibold text-slate-900">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200">
            <h3 class="font-medium text-slate-800 mb-3">Documents récemment ajoutés</h3>
            <ul class="text-sm divide-y divide-slate-100">
                @foreach ($recents as $d)
                    <li class="py-2"><a href="{{ route('documents.show', $d) }}" wire:navigate class="hover:underline">{{ $d->nom }}</a></li>
                @endforeach
            </ul>
        </div>

        <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200">
            <h3 class="font-medium text-slate-800 mb-3">Documents récemment modifiés</h3>
            <ul class="text-sm divide-y divide-slate-100">
                @foreach ($recemmentModifies as $d)
                    <li class="py-2"><a href="{{ route('documents.show', $d) }}" wire:navigate class="hover:underline">{{ $d->nom }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="rounded-lg bg-white p-4 ring-1 ring-slate-200">
        <h3 class="font-medium text-slate-800 mb-3">Activité récente</h3>
        <ul class="text-sm divide-y divide-slate-100">
            @foreach ($activiteRecente as $log)
                <li class="py-2 text-slate-600">
                    <span class="font-medium">{{ $log->utilisateur?->name ?? 'Système' }}</span>
                    — {{ $log->action }} — <span class="text-slate-400">{{ $log->created_at->diffForHumans() }}</span>
                </li>
            @endforeach
        </ul>
    </div>
</div>
