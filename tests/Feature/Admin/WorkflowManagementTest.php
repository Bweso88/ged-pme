<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Workflow\Services\WorkflowService;
use App\Livewire\Admin\Workflows\Index;
use App\Models\Company;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class WorkflowManagementTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    private function makeDocument(Company $company, User $author): Document
    {
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $author->id]);

        return Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'contrat.txt', 'auteur_id' => $author->id, 'proprietaire_id' => $author->id,
        ]);
    }

    public function test_renaming_a_workflow_updates_its_name_and_type(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);
        $definition = WorkflowDefinition::query()->create(['nom' => 'Ancien nom']);

        Livewire::test(Index::class)
            ->call('startRename', $definition->id)
            ->set('renameNom', 'Nouveau nom')
            ->call('updateWorkflow')
            ->assertHasNoErrors();

        $this->assertSame('Nouveau nom', $definition->fresh()->nom);
    }

    public function test_toggling_active_flips_the_flag(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);
        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation']);

        Livewire::test(Index::class)->call('toggleActive', $definition->id);
        $this->assertFalse($definition->fresh()->actif);

        Livewire::test(Index::class)->call('toggleActive', $definition->id);
        $this->assertTrue($definition->fresh()->actif);
    }

    public function test_deleting_an_unused_workflow_removes_it_and_its_steps(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);
        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation']);
        $managerRole = Role::query()->whereNull('company_id')->where('code', Role::MANAGER)->firstOrFail();
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Manager', 'role_requis_id' => $managerRole->id]);

        Livewire::test(Index::class)->call('deleteWorkflow', $definition->id)->assertHasNoErrors();

        $this->assertNull(WorkflowDefinition::find($definition->id));
        $this->assertSame(0, WorkflowStep::where('workflow_definition_id', $definition->id)->count());
    }

    public function test_deleting_a_workflow_already_used_by_a_document_is_refused_with_a_clear_message(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();
        $author = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($company, $author);

        $managerRole = Role::query()->whereNull('company_id')->where('code', Role::MANAGER)->firstOrFail();
        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation']);
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Manager', 'role_requis_id' => $managerRole->id]);

        app(WorkflowService::class)->submit($document, $author);

        $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);
        Livewire::test(Index::class)->call('deleteWorkflow', $definition->id)->assertHasErrors('delete');

        $this->assertNotNull(WorkflowDefinition::find($definition->id));
    }
}
