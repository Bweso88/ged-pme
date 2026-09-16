<?php

declare(strict_types=1);

namespace App\Domain\Archiving\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\ArchiveRecord;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\RetentionPolicy;
use App\Models\User;
use App\Notifications\RetentionDueSoon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cycle de vie archivage/rétention (voir doc ged-pme/docs/04-securite-workflow-archivage.md, §14).
 * Garde-fou non contournable : aucune méthode de cette classe ne détruit physiquement un
 * document sans passage par validateDestruction(), qui exige un utilisateur et un motif.
 */
class ArchiveService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function archive(Document $document, User $user, ?string $categorie, string $confidentialite, ?string $motif): ArchiveRecord
    {
        $document->forceFill(['statut' => Document::STATUT_ARCHIVE, 'confidentialite' => $confidentialite])->save();

        $policy = $document->document_type_id !== null
            ? RetentionPolicy::query()->where('document_type_id', $document->document_type_id)->where('actif', true)->first()
            : null;

        $record = ArchiveRecord::query()->create([
            'document_id' => $document->id,
            'date_archivage' => now(),
            'categorie' => $categorie ?? $policy?->categorie_archive,
            'confidentialite' => $confidentialite,
            'motif' => $motif,
            'date_destruction_prevue' => $policy !== null
                ? now()->addMonths($policy->duree_conservation_mois)->toDateString()
                : null,
            'statut' => ArchiveRecord::STATUT_ACTIF,
        ]);

        $this->auditLogger->log($user, AuditLog::ARCHIVAGE, $document, ['categorie' => $record->categorie]);

        return $record;
    }

    /**
     * Tâche planifiée (voir App\Console\Commands\DetectRetentionDueDocuments) : détecte les
     * archives arrivées à échéance et crée une PROPOSITION — ne détruit jamais rien elle-même.
     */
    public function detectDueRecords(): int
    {
        $due = ArchiveRecord::withoutTenantScope()
            ->where('statut', ArchiveRecord::STATUT_ACTIF)
            ->whereNotNull('date_destruction_prevue')
            ->where('date_destruction_prevue', '<=', now()->toDateString())
            ->get();

        foreach ($due as $record) {
            $record->forceFill(['statut' => ArchiveRecord::STATUT_PROPOSE_DESTRUCTION])->save();

            $document = Document::withoutTenantScope()->find($record->document_id);
            if ($document === null) {
                continue;
            }

            $responsables = User::withoutGlobalScope('tenant')
                ->where('company_id', $record->company_id)
                ->whereHas('roles.permissions', fn ($q) => $q->where('code', 'admin.settings'))
                ->get();

            $responsables->each(fn (User $u) => $u->notify(new RetentionDueSoon($document)));
        }

        return $due->count();
    }

    /** Validation humaine explicite obligatoire avant toute destruction — jamais automatique. */
    public function validateDestruction(ArchiveRecord $record, User $validator, string $motif): void
    {
        DB::transaction(function () use ($record, $validator, $motif) {
            $record->forceFill([
                'statut' => ArchiveRecord::STATUT_VALIDE_DESTRUCTION,
                'motif' => $motif,
                'valide_par' => $validator->id,
                'valide_at' => now(),
            ])->save();

            $this->auditLogger->log($validator, AuditLog::SUPPRESSION, $record->document, [
                'action' => 'validation_destruction_archive', 'motif' => $motif,
            ]);
        });
    }

    public function extendRetention(ArchiveRecord $record, User $user, int $moisSupplementaires): void
    {
        $record->forceFill([
            'statut' => ArchiveRecord::STATUT_ACTIF,
            'date_destruction_prevue' => Carbon::parse($record->date_destruction_prevue)->addMonths($moisSupplementaires),
        ])->save();

        $this->auditLogger->log($user, AuditLog::MODIFICATION, $record->document, ['action' => 'prolongation_conservation']);
    }
}
