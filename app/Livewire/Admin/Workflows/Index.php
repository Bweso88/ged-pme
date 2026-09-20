<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Workflows;

use App\Models\DocumentType;
use App\Models\Role;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $nom = '';

    public string $documentTypeId = '';

    public ?string $editingWorkflowId = null;

    public string $stepNom = '';

    public string $stepRoleId = '';

    public ?string $renamingWorkflowId = null;

    public string $renameNom = '';

    public string $renameDocumentTypeId = '';

    public function mount(): void
    {
        Gate::authorize('admin.settings');
    }

    public function createWorkflow(): void
    {
        $this->validate(['nom' => ['required', 'string', 'max:255']]);

        WorkflowDefinition::query()->create([
            'nom' => $this->nom,
            'document_type_id' => $this->documentTypeId ?: null,
        ]);

        $this->reset(['nom', 'documentTypeId']);
    }

    public function manageSteps(string $workflowId): void
    {
        $this->editingWorkflowId = $workflowId;
    }

    public function startRename(string $workflowId): void
    {
        $workflow = WorkflowDefinition::query()->findOrFail($workflowId);

        $this->renamingWorkflowId = $workflow->id;
        $this->renameNom = $workflow->nom;
        $this->renameDocumentTypeId = (string) $workflow->document_type_id;
    }

    public function updateWorkflow(): void
    {
        $this->validate(['renameNom' => ['required', 'string', 'max:255']]);

        $workflow = WorkflowDefinition::query()->findOrFail($this->renamingWorkflowId);
        $workflow->forceFill([
            'nom' => $this->renameNom,
            'document_type_id' => $this->renameDocumentTypeId ?: null,
        ])->save();

        $this->reset(['renamingWorkflowId', 'renameNom', 'renameDocumentTypeId']);
    }

    public function toggleActive(string $workflowId): void
    {
        $workflow = WorkflowDefinition::query()->findOrFail($workflowId);
        $workflow->forceFill(['actif' => ! $workflow->actif])->save();
    }

    public function deleteWorkflow(string $workflowId): void
    {
        $workflow = WorkflowDefinition::query()->findOrFail($workflowId);

        try {
            // DB::transaction() ouvre un savepoint : si le DELETE échoue (contrainte
            // restrictOnDelete), seul ce savepoint est annulé et la requête suivante
            // (le re-render du composant) reste utilisable, sans « poisoner » toute la
            // transaction en cours au niveau PostgreSQL.
            DB::transaction(fn () => $workflow->delete());
        } catch (QueryException $exception) {
            $this->addError(
                'delete',
                "Impossible de supprimer « {$workflow->nom} » : il a déjà été utilisé par au moins un document. Désactivez-le plutôt (bouton « Désactiver »).",
            );

            return;
        }

        if ($this->editingWorkflowId === $workflowId) {
            $this->editingWorkflowId = null;
        }
    }

    public function removeStep(string $stepId): void
    {
        $step = WorkflowStep::query()->findOrFail($stepId);

        // workflow_instances.etape_courante_id est en nullOnDelete (pas restrictOnDelete) :
        // sans ce contrôle applicatif, PostgreSQL accepterait silencieusement la suppression
        // d'une étape actuellement en attente sur un document, laissant l'instance orpheline.
        if (WorkflowInstance::query()->where('etape_courante_id', $step->id)->where('statut', 'en_cours')->exists()) {
            $this->addError(
                'delete',
                "Impossible de retirer l'étape « {$step->nom} » : au moins un document est actuellement en attente de validation sur cette étape.",
            );

            return;
        }

        try {
            DB::transaction(fn () => $step->delete());
        } catch (QueryException $exception) {
            $this->addError(
                'delete',
                "Impossible de supprimer l'étape « {$step->nom} » : elle a déjà été utilisée dans un circuit de validation.",
            );
        }
    }

    public function addStep(): void
    {
        $this->validate([
            'stepNom' => ['required', 'string', 'max:255'],
            'stepRoleId' => ['required', 'exists:roles,id'],
        ]);

        $workflow = WorkflowDefinition::query()->findOrFail($this->editingWorkflowId);
        $ordre = $workflow->steps()->max('ordre') + 1;

        WorkflowStep::query()->create([
            'workflow_definition_id' => $workflow->id,
            'ordre' => $ordre,
            'nom' => $this->stepNom,
            'role_requis_id' => $this->stepRoleId,
        ]);

        $this->reset(['stepNom', 'stepRoleId']);
    }

    public function render()
    {
        return view('livewire.admin.workflows.index', [
            'workflows' => WorkflowDefinition::query()->with('steps.roleRequis', 'documentType')->orderBy('nom')->get(),
            'documentTypes' => DocumentType::query()->orderBy('nom')->get(),
            'roles' => Role::availableFor(Auth::user()->company)->orderBy('nom')->get(),
        ]);
    }
}
