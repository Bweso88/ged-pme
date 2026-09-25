@php
    $statutStyles = [
        'brouillon' => ['bg-slate-100', 'text-slate-600'],
        'soumis' => ['bg-sky-100', 'text-sky-700'],
        'en_validation' => ['bg-amber-100', 'text-amber-700'],
        'publie' => ['bg-emerald-100', 'text-emerald-700'],
        'archive' => ['bg-violet-100', 'text-violet-700'],
    ];
    $actionStyles = [
        'creation' => ['bg-emerald-100', 'text-emerald-700'],
        'modification' => ['bg-sky-100', 'text-sky-700'],
        'suppression' => ['bg-red-100', 'text-red-700'],
        'validation' => ['bg-violet-100', 'text-violet-700'],
        'archivage' => ['bg-amber-100', 'text-amber-700'],
        'partage' => ['bg-brand-100', 'text-brand-700'],
    ];
@endphp
<div class="space-y-6" x-data="{ initCharts() {
        if (typeof Chart === 'undefined' || this._chartsReady) return;
        this._chartsReady = true;

        new Chart(this.$refs.weeklyChart, {
            type: 'line',
            data: {
                labels: @js(collect($documentsParSemaine)->pluck('label')),
                datasets: [{
                    label: 'Documents importés',
                    data: @js(collect($documentsParSemaine)->pluck('total')),
                    borderColor: '#6c4de0',
                    backgroundColor: 'rgba(108, 77, 224, 0.12)',
                    tension: 0.35,
                    fill: true,
                    pointRadius: 3,
                    pointBackgroundColor: '#6c4de0',
                }],
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            },
        });

        new Chart(this.$refs.statusChart, {
            type: 'doughnut',
            data: {
                labels: @js(collect($documentsParStatut)->pluck('label')),
                datasets: [{
                    data: @js(collect($documentsParStatut)->pluck('total')),
                    backgroundColor: ['#cbd5e1', '#7dd3fc', '#fcd34d', '#6ee7b7', '#c4b5fd'],
                    borderWidth: 0,
                }],
            },
            options: {
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 11 } } } },
                cutout: '65%',
            },
        });
    } }" x-init="initCharts()">

    {{-- Cartes statistiques --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
        @foreach ([
            ['Documents', $totalDocuments, 'documents', 'bg-brand-100 text-brand-600'],
            ['En attente de validation', $enAttenteValidation, 'clock', 'bg-amber-100 text-amber-600'],
            ['Mes tâches', $tachesEnAttente, 'check', 'bg-sky-100 text-sky-600'],
            ['Archives actives', $archivesActives, 'archive', 'bg-violet-100 text-violet-600'],
            ['Propositions de destruction', $propositionsDestruction, 'trash', 'bg-red-100 text-red-600'],
            ['Échéances < 90 jours', $echeancesProches, 'flag', 'bg-emerald-100 text-emerald-600'],
        ] as [$label, $value, $icon, $badgeClasses])
            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-100">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $badgeClasses }}">
                    <x-icon :name="$icon" class="h-5 w-5" />
                </span>
                <p class="mt-3 text-2xl font-semibold text-slate-900">{{ $value }}</p>
                <p class="text-sm text-slate-500">{{ $label }}</p>
            </div>
        @endforeach
    </div>

    {{-- Graphiques --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-slate-800">Documents importés — 8 dernières semaines</h3>
            </div>
            <div class="h-64">
                <canvas x-ref="weeklyChart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <h3 class="font-semibold text-slate-800 mb-4">Répartition par statut</h3>
            <div class="h-64">
                <canvas x-ref="statusChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Tableaux --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <h3 class="font-semibold text-slate-800 mb-3">En attente de validation</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-slate-400">
                    <tr>
                        <th class="pb-2 font-medium">Document</th>
                        <th class="pb-2 font-medium">Étape</th>
                        <th class="pb-2 font-medium"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($instancesEnAttente as $instance)
                        <tr>
                            <td class="py-2.5 pr-2 text-slate-800 font-medium truncate max-w-[12rem]">{{ $instance->document?->nom }}</td>
                            <td class="py-2.5 pr-2 text-slate-500">{{ $instance->etapeCourante?->nom ?? '—' }}</td>
                            <td class="py-2.5 text-right">
                                @if ($instance->document)
                                    <a href="{{ route('documents.show', $instance->document) }}" wire:navigate class="text-brand-600 hover:underline">Ouvrir</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-slate-400">Rien en attente de validation.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <h3 class="font-semibold text-slate-800 mb-3">Activité récente</h3>
            <table class="w-full text-sm">
                <thead class="text-left text-slate-400">
                    <tr>
                        <th class="pb-2 font-medium">Utilisateur</th>
                        <th class="pb-2 font-medium">Action</th>
                        <th class="pb-2 font-medium text-right">Quand</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($activiteRecente as $log)
                        <tr>
                            <td class="py-2.5 pr-2 text-slate-800 font-medium truncate max-w-[8rem]">{{ $log->utilisateur?->name ?? 'Système' }}</td>
                            <td class="py-2.5 pr-2">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ ($actionStyles[$log->action] ?? ['bg-slate-100', 'text-slate-600'])[0] }} {{ ($actionStyles[$log->action] ?? ['bg-slate-100', 'text-slate-600'])[1] }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="py-2.5 text-right text-slate-400">{{ $log->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-6 text-center text-slate-400">Aucune activité récente.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Documents récents --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <h3 class="font-semibold text-slate-800 mb-3">Documents récemment ajoutés</h3>
            <ul class="text-sm divide-y divide-slate-100">
                @forelse ($recents as $d)
                    <li class="py-2 flex items-center justify-between">
                        <a href="{{ route('documents.show', $d) }}" wire:navigate class="text-slate-700 hover:text-brand-600 truncate">{{ $d->nom }}</a>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ ($statutStyles[$d->statut] ?? ['bg-slate-100', 'text-slate-600'])[0] }} {{ ($statutStyles[$d->statut] ?? ['bg-slate-100', 'text-slate-600'])[1] }}">
                            {{ $d->statut }}
                        </span>
                    </li>
                @empty
                    <li class="py-4 text-center text-slate-400">Aucun document.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-100">
            <h3 class="font-semibold text-slate-800 mb-3">Documents récemment modifiés</h3>
            <ul class="text-sm divide-y divide-slate-100">
                @forelse ($recemmentModifies as $d)
                    <li class="py-2">
                        <a href="{{ route('documents.show', $d) }}" wire:navigate class="text-slate-700 hover:text-brand-600 truncate">{{ $d->nom }}</a>
                    </li>
                @empty
                    <li class="py-4 text-center text-slate-400">Aucun document.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
