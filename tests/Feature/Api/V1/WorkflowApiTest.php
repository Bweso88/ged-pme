<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Document;
use App\Models\Folder;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class WorkflowApiTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    private function makeDocument(User $author): Document
    {
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $author->id]);

        return Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'contrat.txt', 'auteur_id' => $author->id, 'proprietaire_id' => $author->id,
        ]);
    }

    public function test_submitting_starts_the_workflow(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();
        $author = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($author);

        $managerRole = Role::query()->whereNull('company_id')->where('code', Role::MANAGER)->firstOrFail();
        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation simple']);
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Manager', 'role_requis_id' => $managerRole->id]);

        Sanctum::actingAs($author);

        $this->postJson("/api/v1/documents/{$document->id}/workflow/submit")
            ->assertOk()
            ->assertJsonPath('data.statut', Document::STATUT_SOUMIS);
    }

    public function test_approving_each_step_eventually_publishes_the_document(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();
        $author = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($author);

        $managerRole = Role::query()->whereNull('company_id')->where('code', Role::MANAGER)->firstOrFail();
        $adminRole = Role::query()->whereNull('company_id')->where('code', Role::ADMIN_ENTREPRISE)->firstOrFail();

        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation contrat']);
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Manager', 'role_requis_id' => $managerRole->id]);
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 2, 'nom' => 'Direction', 'role_requis_id' => $adminRole->id]);

        $manager = $this->actingAsCompanyUser($company, Role::MANAGER);
        $admin = $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);

        Sanctum::actingAs($author);
        $this->postJson("/api/v1/documents/{$document->id}/workflow/submit")->assertOk();

        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/documents/{$document->id}/workflow/approve")
            ->assertOk()
            ->assertJsonPath('data.statut', Document::STATUT_EN_VALIDATION);

        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/documents/{$document->id}/workflow/approve")
            ->assertOk()
            ->assertJsonPath('data.statut', Document::STATUT_PUBLIE);
    }

    public function test_rejecting_requires_a_comment_and_sends_the_document_back_to_draft(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();
        $author = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($author);

        $managerRole = Role::query()->whereNull('company_id')->where('code', Role::MANAGER)->firstOrFail();
        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation simple']);
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Manager', 'role_requis_id' => $managerRole->id]);

        $manager = $this->actingAsCompanyUser($company, Role::MANAGER);

        Sanctum::actingAs($author);
        $this->postJson("/api/v1/documents/{$document->id}/workflow/submit")->assertOk();

        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/documents/{$document->id}/workflow/reject")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('commentaire');

        $this->postJson("/api/v1/documents/{$document->id}/workflow/reject", ['commentaire' => 'Montant incorrect'])
            ->assertOk()
            ->assertJsonPath('data.statut', Document::STATUT_BROUILLON);
    }

    public function test_a_user_with_the_wrong_role_cannot_approve_the_step(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();
        $author = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($author);

        $adminRole = Role::query()->whereNull('company_id')->where('code', Role::ADMIN_ENTREPRISE)->firstOrFail();
        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation simple']);
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Direction', 'role_requis_id' => $adminRole->id]);

        $manager = $this->actingAsCompanyUser($company, Role::MANAGER);

        Sanctum::actingAs($author);
        $this->postJson("/api/v1/documents/{$document->id}/workflow/submit")->assertOk();

        Sanctum::actingAs($manager);
        $this->postJson("/api/v1/documents/{$document->id}/workflow/approve")->assertUnprocessable();
    }
}
