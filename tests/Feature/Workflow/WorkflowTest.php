<?php

declare(strict_types=1);

namespace Tests\Feature\Workflow;

use App\Domain\Workflow\Services\WorkflowService;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Role;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    private function makeDocument(\App\Models\Company $company, \App\Models\User $author): Document
    {
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $author->id]);

        return Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'contrat.txt', 'auteur_id' => $author->id, 'proprietaire_id' => $author->id,
        ]);
    }

    public function test_document_goes_through_two_step_workflow_to_publication(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();

        $author = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($company, $author);

        $managerRole = Role::query()->whereNull('company_id')->where('code', Role::MANAGER)->firstOrFail();
        $adminRole = Role::query()->whereNull('company_id')->where('code', Role::ADMIN_ENTREPRISE)->firstOrFail();

        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation contrat']);
        $step1 = WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Manager', 'role_requis_id' => $managerRole->id]);
        $step2 = WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 2, 'nom' => 'Direction', 'role_requis_id' => $adminRole->id]);

        $manager = $this->actingAsCompanyUser($company, Role::MANAGER);
        $admin = $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);

        $service = app(WorkflowService::class);
        $instance = $service->submit($document, $author);

        $this->assertSame(Document::STATUT_SOUMIS, $document->fresh()->statut);
        $this->assertSame($step1->id, $instance->fresh()->etape_courante_id);

        $service->approve($instance->fresh(), $manager);
        $this->assertSame(Document::STATUT_EN_VALIDATION, $document->fresh()->statut);
        $this->assertSame($step2->id, $instance->fresh()->etape_courante_id);

        $service->approve($instance->fresh(), $admin);
        $this->assertSame(Document::STATUT_PUBLIE, $document->fresh()->statut);
        $this->assertSame('termine', $instance->fresh()->statut);
        $this->assertSame(2, $instance->fresh()->actions()->count());
    }

    public function test_rejection_sends_document_back_to_draft_with_reason(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();

        $author = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($company, $author);

        $managerRole = Role::query()->whereNull('company_id')->where('code', Role::MANAGER)->firstOrFail();
        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation simple']);
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Manager', 'role_requis_id' => $managerRole->id]);

        $manager = $this->actingAsCompanyUser($company, Role::MANAGER);

        $service = app(WorkflowService::class);
        $instance = $service->submit($document, $author);
        $service->reject($instance->fresh(), $manager, 'Montant incorrect');

        $this->assertSame(Document::STATUT_BROUILLON, $document->fresh()->statut);
        $this->assertSame('rejete', $instance->fresh()->statut);
        $this->assertSame('Montant incorrect', $instance->fresh()->actions()->latest()->first()->commentaire);
    }

    public function test_user_without_the_required_role_cannot_validate_the_step(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();

        $author = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($company, $author);

        $managerRole = Role::query()->whereNull('company_id')->where('code', Role::MANAGER)->firstOrFail();
        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation simple']);
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Manager', 'role_requis_id' => $managerRole->id]);

        $employe = $this->actingAsCompanyUser($company, Role::EMPLOYE);

        $service = app(WorkflowService::class);
        $instance = $service->submit($document, $author);

        $this->expectException(ValidationException::class);
        $service->approve($instance->fresh(), $employe);
    }
}
