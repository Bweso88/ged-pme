<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Domain\Workflow\Services\WorkflowService;
use App\Models\ArchiveRecord;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\WorkflowInstance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    /** Approuve directement depuis le tableau de bord (sans commentaire) — le rejet, qui exige
     * un motif, reste sur la fiche document. */
    public function approve(string $instanceId, WorkflowService $service): void
    {
        $instance = WorkflowInstance::query()->with('document')->findOrFail($instanceId);

        Gate::authorize('validateWorkflow', $instance->document);

        $service->approve($instance, Auth::user());
    }

    public function render()
    {
        $user = Auth::user();

        $instancesEnCours = WorkflowInstance::query()
            ->where('statut', WorkflowInstance::STATUT_EN_COURS)
            ->whereNotNull('etape_courante_id')
            ->with(['document', 'etapeCourante'])
            ->orderByDesc('created_at')
            ->get();

        $tachesEnAttente = $instancesEnCours->filter(fn (WorkflowInstance $i) => $i->etapeCourante?->canBeActedOnBy($user))->count();

        $documentsParSemaine = $this->weeklyDocumentCounts();
        $semaineActuelle = $documentsParSemaine[count($documentsParSemaine) - 1]['total'];
        $semainePrecedente = $documentsParSemaine[count($documentsParSemaine) - 2]['total'];
        $documentsDeltaPct = $semainePrecedente > 0
            ? (int) round((($semaineActuelle - $semainePrecedente) / $semainePrecedente) * 100)
            : null;

        return view('livewire.dashboard.index', [
            'totalDocuments' => Document::query()->count(),
            'documentsDeltaPct' => $documentsDeltaPct,
            'recents' => Document::query()->orderByDesc('created_at')->limit(5)->get(),
            'recemmentModifies' => Document::query()->orderByDesc('updated_at')->limit(5)->get(),
            'enAttenteValidation' => WorkflowInstance::query()->where('statut', WorkflowInstance::STATUT_EN_COURS)->count(),
            'instancesEnAttente' => $instancesEnCours->take(6)->map(fn (WorkflowInstance $i) => [
                'instance' => $i,
                'peut_valider' => (bool) $i->etapeCourante?->canBeActedOnBy($user),
            ]),
            'aArchiver' => Document::query()->where('statut', Document::STATUT_PUBLIE)->count(),
            'echeancesProches' => Document::query()->whereNotNull('date_expiration')->where('date_expiration', '<=', now()->addDays(90))->count(),
            'archivesActives' => ArchiveRecord::query()->where('statut', ArchiveRecord::STATUT_ACTIF)->count(),
            'propositionsDestruction' => ArchiveRecord::query()->where('statut', ArchiveRecord::STATUT_PROPOSE_DESTRUCTION)->count(),
            'tachesEnAttente' => $tachesEnAttente,
            'activiteRecente' => AuditLog::query()->with('utilisateur')->orderByDesc('created_at')->limit(6)->get(),
            'documentsParSemaine' => $documentsParSemaine,
            'documentsParStatut' => $this->statusBreakdown(),
        ]);
    }

    /** @return list<array{label: string, total: int}> 8 dernières semaines, semaines sans dépôt incluses à 0. */
    private function weeklyDocumentCounts(): array
    {
        $start = now()->startOfWeek()->subWeeks(7);

        $rows = Document::query()
            ->where('created_at', '>=', $start)
            ->selectRaw("date_trunc('week', created_at) as semaine, count(*) as total")
            ->groupBy('semaine')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[Carbon::parse($row->semaine)->format('Y-m-d')] = (int) $row->total;
        }

        $series = [];
        for ($i = 0; $i < 8; $i++) {
            $weekStart = $start->copy()->addWeeks($i);
            $series[] = ['label' => $weekStart->format('d/m'), 'total' => $counts[$weekStart->format('Y-m-d')] ?? 0];
        }

        return $series;
    }

    /** @return list<array{label: string, total: int}> Tous les statuts, y compris ceux à 0 document. */
    private function statusBreakdown(): array
    {
        $labels = [
            Document::STATUT_BROUILLON => 'Brouillon',
            Document::STATUT_SOUMIS => 'Soumis',
            Document::STATUT_EN_VALIDATION => 'En validation',
            Document::STATUT_PUBLIE => 'Publié',
            Document::STATUT_ARCHIVE => 'Archivé',
        ];

        $counts = Document::query()->selectRaw('statut, count(*) as total')->groupBy('statut')->pluck('total', 'statut');

        return collect($labels)
            ->map(fn (string $label, string $statut) => ['label' => $label, 'total' => (int) ($counts[$statut] ?? 0)])
            ->values()
            ->all();
    }
}
