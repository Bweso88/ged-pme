<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/**
 * Un rôle "Lecteur" (document.view seul, pas document.download — voir SystemRoleSeeder)
 * doit pouvoir consulter un document via /preview sans jamais pouvoir le télécharger via
 * /download, et l'API doit refléter ce droit pour que le mobile masque le bouton adéquat.
 */
class DocumentPreviewTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function makeDocumentWithVersion(string $roleCode, string $mimeType = 'application/pdf'): array
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, $roleCode);

        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id,
            'nom' => 'scan.pdf',
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        Storage::disk('local')->put('tenants/test/documents/scan.pdf', '%PDF-1.4 contenu factice');

        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'numero_version' => 1,
            'storage_path' => 'tenants/test/documents/scan.pdf',
            'taille_octets' => 24,
            'hash_sha256' => str_repeat('a', 64),
            'mime_type' => $mimeType,
            'auteur_id' => $user->id,
        ]);
        $document->forceFill(['version_courante_id' => $version->id])->save();

        $this->app['auth']->forgetGuards();
        Sanctum::actingAs($user);

        return [$document, $user];
    }

    public function test_a_reader_can_preview_but_not_download(): void
    {
        [$document] = $this->makeDocumentWithVersion(Role::LECTEUR);

        $this->getJson("/api/v1/documents/{$document->id}")
            ->assertOk()
            ->assertJsonPath('data.peut_telecharger', false);

        $this->get("/api/v1/documents/{$document->id}/preview")->assertOk();
        $this->get("/api/v1/documents/{$document->id}/download")->assertForbidden();
    }

    public function test_an_employee_can_preview_and_download(): void
    {
        [$document] = $this->makeDocumentWithVersion(Role::EMPLOYE);

        $this->getJson("/api/v1/documents/{$document->id}")
            ->assertOk()
            ->assertJsonPath('data.peut_telecharger', true);

        $this->get("/api/v1/documents/{$document->id}/preview")->assertOk();
        $this->get("/api/v1/documents/{$document->id}/download")->assertOk();
    }

    public function test_preview_of_a_non_previewable_file_type_returns_415(): void
    {
        [$document] = $this->makeDocumentWithVersion(
            Role::EMPLOYE,
            mimeType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );

        $this->get("/api/v1/documents/{$document->id}/preview")
            ->assertStatus(415);
    }
}
