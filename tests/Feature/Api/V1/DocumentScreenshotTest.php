<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/**
 * iOS ne peut pas bloquer une capture d'écran (contrairement à Android, protégé en amont par
 * FLAG_SECURE) — seulement en être notifié après coup. L'app mobile reporte alors l'événement
 * ici, pour qu'il reste au moins tracé dans le journal d'audit.
 */
class DocumentScreenshotTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_a_reader_can_report_a_screenshot_of_a_document_they_can_view(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::LECTEUR);

        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id,
            'nom' => 'scan.pdf',
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/documents/{$document->id}/screenshot")
            ->assertNoContent();

        $this->assertDatabaseHas('audit_logs', [
            'company_id' => $company->id,
            'utilisateur_id' => $user->id,
            'action' => AuditLog::CAPTURE_ECRAN,
            'ressource_type' => 'document',
            'ressource_id' => $document->id,
        ]);
    }

    public function test_reporting_a_screenshot_of_a_document_from_another_company_is_not_found(): void
    {
        $this->seedCatalog();
        $otherCompany = $this->createCompany('Autre entreprise');
        $otherUser = $this->actingAsCompanyUser($otherCompany, Role::EMPLOYE);
        $otherFolder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $otherUser->id]);
        $document = Document::query()->create([
            'folder_id' => $otherFolder->id,
            'nom' => 'scan.pdf',
            'auteur_id' => $otherUser->id,
            'proprietaire_id' => $otherUser->id,
        ]);

        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/documents/{$document->id}/screenshot")
            ->assertNotFound();
    }
}
