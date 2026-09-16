<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Models\ArchiveRecord;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\WorkflowInstance;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public function render()
    {
        $user = Auth::user();

        $tachesEnAttente = WorkflowInstance::query()
            ->where('statut', WorkflowInstance::STATUT_EN_COURS)
            ->whereNotNull('etape_courante_id')
            ->with('etapeCourante')
            ->get()
            ->filter(fn (WorkflowInstance $i) => $i->etapeCourante?->canBeActedOnBy($user))
            ->count();

        return view('livewire.dashboard.index', [
            'totalDocuments' => Document::query()->count(),
            'recents' => Document::query()->orderByDesc('created_at')->limit(5)->get(),
            'recemmentModifies' => Document::query()->orderByDesc('updated_at')->limit(5)->get(),
            'enAttenteValidation' => WorkflowInstance::query()->where('statut', WorkflowInstance::STATUT_EN_COURS)->count(),
            'aArchiver' => Document::query()->where('statut', Document::STATUT_PUBLIE)->count(),
            'echeancesProches' => Document::query()->whereNotNull('date_expiration')->where('date_expiration', '<=', now()->addDays(90))->count(),
            'archivesActives' => ArchiveRecord::query()->where('statut', ArchiveRecord::STATUT_ACTIF)->count(),
            'propositionsDestruction' => ArchiveRecord::query()->where('statut', ArchiveRecord::STATUT_PROPOSE_DESTRUCTION)->count(),
            'tachesEnAttente' => $tachesEnAttente,
            'activiteRecente' => AuditLog::query()->with('utilisateur')->orderByDesc('created_at')->limit(8)->get(),
        ]);
    }
}
