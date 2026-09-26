<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Workflow\Services\WorkflowService;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Role;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class TaskApiTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_it_lists_only_tasks_the_authenticated_user_can_act_on(): void
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
        app(WorkflowService::class)->submit($document, $author);

        Sanctum::actingAs($author);
        $this->getJson('/api/v1/tasks')->assertOk()->assertJsonCount(0, 'data');

        Sanctum::actingAs($manager);
        $this->getJson('/api/v1/tasks')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.document.id', $document->id);
    }
}
