<?php

declare(strict_types=1);

namespace App\Domain\Workflow\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Notifications\DocumentSubmittedForValidation;
use App\Notifications\DocumentValidationDecision;
use Illuminate\Validation\ValidationException;

/**
 * Moteur de workflow linéaire et configurable (voir doc ged-pme/docs/04-securite-workflow-archivage.md,
 * §13) : un document suit une suite ordonnée d'étapes, chacune associée à un rôle ou une
 * personne nommée. Chaque décision est tracée de façon immuable dans workflow_actions.
 */
class WorkflowService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function submit(Document $document, User $author): WorkflowInstance
    {
        $definition = WorkflowDefinition::query()
            ->where('actif', true)
            ->where(function ($query) use ($document) {
                $query->whereNull('document_type_id')->orWhere('document_type_id', $document->document_type_id);
            })
            ->first();

        if ($definition === null) {
            throw ValidationException::withMessages(['workflow' => 'Aucun workflow n\'est configuré pour ce type de document.']);
        }

        $firstStep = $definition->steps()->orderBy('ordre')->first();

        if ($firstStep === null) {
            throw ValidationException::withMessages(['workflow' => 'Ce workflow ne comporte aucune étape.']);
        }

        $instance = WorkflowInstance::query()->create([
            'document_id' => $document->id,
            'workflow_definition_id' => $definition->id,
            'etape_courante_id' => $firstStep->id,
            'statut' => WorkflowInstance::STATUT_EN_COURS,
        ]);

        $document->forceFill(['statut' => Document::STATUT_SOUMIS])->save();

        $this->auditLogger->log($author, AuditLog::MODIFICATION, $document, ['action' => 'soumission_workflow']);
        $this->notifyStepApprovers($firstStep, $document);

        return $instance;
    }

    public function approve(WorkflowInstance $instance, User $approver, ?string $commentaire = null): void
    {
        $this->assertCanAct($instance, $approver);

        $this->recordAction($instance, $approver, WorkflowAction::APPROUVE, $commentaire);

        $nextStep = $instance->workflowDefinition->steps()
            ->where('ordre', '>', $instance->etapeCourante->ordre)
            ->orderBy('ordre')
            ->first();

        if ($nextStep === null) {
            $instance->forceFill(['statut' => WorkflowInstance::STATUT_TERMINE, 'etape_courante_id' => null])->save();
            $instance->document->forceFill(['statut' => Document::STATUT_PUBLIE])->save();
            $instance->document->auteur?->notify(new DocumentValidationDecision($instance->document, WorkflowAction::APPROUVE));

            return;
        }

        $instance->forceFill(['etape_courante_id' => $nextStep->id])->save();
        $instance->document->forceFill(['statut' => Document::STATUT_EN_VALIDATION])->save();
        $this->notifyStepApprovers($nextStep, $instance->document);
    }

    public function reject(WorkflowInstance $instance, User $approver, string $commentaire): void
    {
        $this->assertCanAct($instance, $approver);

        $this->recordAction($instance, $approver, WorkflowAction::REJETE, $commentaire);

        $instance->forceFill(['statut' => WorkflowInstance::STATUT_REJETE, 'etape_courante_id' => null])->save();
        $instance->document->forceFill(['statut' => Document::STATUT_BROUILLON])->save();
        $instance->document->auteur?->notify(new DocumentValidationDecision($instance->document, WorkflowAction::REJETE, $commentaire));
    }

    public function requestChanges(WorkflowInstance $instance, User $approver, string $commentaire): void
    {
        $this->assertCanAct($instance, $approver);

        $this->recordAction($instance, $approver, WorkflowAction::DEMANDE_MODIFICATION, $commentaire);

        $instance->forceFill(['statut' => WorkflowInstance::STATUT_ANNULE, 'etape_courante_id' => null])->save();
        $instance->document->forceFill(['statut' => Document::STATUT_BROUILLON])->save();
        $instance->document->auteur?->notify(new DocumentValidationDecision($instance->document, WorkflowAction::DEMANDE_MODIFICATION, $commentaire));
    }

    private function assertCanAct(WorkflowInstance $instance, User $approver): void
    {
        if ($instance->statut !== WorkflowInstance::STATUT_EN_COURS || $instance->etapeCourante === null) {
            throw ValidationException::withMessages(['workflow' => 'Ce workflow n\'est plus en cours.']);
        }

        if (! $instance->etapeCourante->canBeActedOnBy($approver)) {
            throw ValidationException::withMessages(['workflow' => 'Vous n\'êtes pas autorisé à valider cette étape.']);
        }
    }

    private function recordAction(WorkflowInstance $instance, User $approver, string $action, ?string $commentaire): void
    {
        WorkflowAction::query()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $instance->etape_courante_id,
            'utilisateur_id' => $approver->id,
            'action' => $action,
            'commentaire' => $commentaire,
        ]);

        $this->auditLogger->log($approver, AuditLog::VALIDATION, $instance->document, ['action' => $action]);
    }

    private function notifyStepApprovers(\App\Models\WorkflowStep $step, Document $document): void
    {
        if ($step->user_requis_id !== null) {
            $step->userRequis?->notify(new DocumentSubmittedForValidation($document));

            return;
        }

        if ($step->role_requis_id !== null) {
            $step->roleRequis?->users()->where('company_id', $document->company_id)->get()
                ->each(fn (User $u) => $u->notify(new DocumentSubmittedForValidation($document)));
        }
    }
}
