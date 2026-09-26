<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Domain\Workflow\Services\WorkflowService;
use App\Livewire\Dashboard\Index;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Role;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_dashboard_renders_without_errors(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $this->actingAsCompanyUser($company, Role::EMPLOYE);

        Livewire::test(Index::class)->assertOk();
    }

    public function test_weekly_document_counts_cover_eight_weeks_including_the_current_one(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        Document::query()->create([
            'folder_id' => $folder->id,
            'nom' => 'note.txt',
            'statut' => Document::STATUT_BROUILLON,
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        $series = Livewire::test(Index::class)->viewData('documentsParSemaine');

        $this->assertCount(8, $series);
        $this->assertSame(1, $series[7]['total']);
        $this->assertSame(0, $series[0]['total']);
    }

    public function test_status_breakdown_lists_every_status_including_those_with_no_document(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        Document::query()->create([
            'folder_id' => $folder->id,
            'nom' => 'facture.pdf',
            'statut' => Document::STATUT_PUBLIE,
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        $breakdown = Livewire::test(Index::class)->viewData('documentsParStatut');

        $this->assertCount(5, $breakdown);
        $this->assertSame(1, collect($breakdown)->firstWhere('label', 'Publié')['total']);
        $this->assertSame(0, collect($breakdown)->firstWhere('label', 'Brouillon')['total']);
    }

    public function test_a_user_can_approve_a_pending_task_directly_from_the_dashboard(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();
        $author = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $author->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'contrat.txt', 'auteur_id' => $author->id, 'proprietaire_id' => $author->id,
        ]);

        $managerRole = Role::query()->whereNull('company_id')->where('code', Role::MANAGER)->firstOrFail();
        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation simple']);
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Manager', 'role_requis_id' => $managerRole->id]);

        $manager = $this->actingAsCompanyUser($company, Role::MANAGER);
        $instance = app(WorkflowService::class)->submit($document, $author);

        Livewire::test(Index::class)
            ->call('approve', $instance->id)
            ->assertHasNoErrors();

        $this->assertSame(Document::STATUT_PUBLIE, $document->fresh()->statut);
    }

    public function test_a_user_without_the_required_role_cannot_approve_from_the_dashboard(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();
        $author = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $author->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'contrat.txt', 'auteur_id' => $author->id, 'proprietaire_id' => $author->id,
        ]);

        $adminRole = Role::query()->whereNull('company_id')->where('code', Role::ADMIN_ENTREPRISE)->firstOrFail();
        $definition = WorkflowDefinition::query()->create(['nom' => 'Validation simple']);
        WorkflowStep::query()->create(['workflow_definition_id' => $definition->id, 'ordre' => 1, 'nom' => 'Direction', 'role_requis_id' => $adminRole->id]);

        $instance = app(WorkflowService::class)->submit($document, $author);

        // Un employé n'a pas la permission document.validate : refusé par la Gate avant même
        // d'atteindre WorkflowService (qui refuserait de toute façon, faute du rôle Direction).
        Livewire::test(Index::class)->call('approve', $instance->id)->assertForbidden();

        $this->assertSame(Document::STATUT_SOUMIS, $document->fresh()->statut);
    }
}
