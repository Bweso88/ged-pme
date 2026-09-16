<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Workflows;

use App\Models\DocumentType;
use App\Models\Role;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Support\Facades\Auth;
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
