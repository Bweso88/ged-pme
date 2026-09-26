<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_it_lists_documents_of_a_folder(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        Sanctum::actingAs($user);

        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        Document::query()->create(['folder_id' => $folder->id, 'nom' => 'facture.pdf', 'auteur_id' => $user->id, 'proprietaire_id' => $user->id]);

        $otherFolder = Folder::query()->create(['nom' => 'RH', 'created_by' => $user->id]);
        Document::query()->create(['folder_id' => $otherFolder->id, 'nom' => 'contrat.pdf', 'auteur_id' => $user->id, 'proprietaire_id' => $user->id]);

        $response = $this->getJson("/api/v1/documents?folder_id={$folder->id}")->assertOk();

        $this->assertSame(['facture.pdf'], collect($response->json('data'))->pluck('nom')->all());
    }

    public function test_it_uploads_a_document_with_a_chosen_type(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        Sanctum::actingAs($user);

        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $type = DocumentType::query()->create(['nom' => 'Facture', 'code' => 'facture']);

        $response = $this->postJson('/api/v1/documents', [
            'folder_id' => $folder->id,
            'document_type_id' => $type->id,
            'file' => UploadedFile::fake()->create('facture.pdf', 10, 'application/pdf'),
        ]);

        $response->assertCreated();
        $this->assertSame(1, $folder->documents()->count());
        $this->assertSame($type->id, $folder->documents()->first()->document_type_id);
    }

    public function test_a_user_cannot_view_a_document_from_another_company(): void
    {
        $this->seedCatalog();

        $companyA = $this->createCompany('A');
        $userA = $this->actingAsCompanyUser($companyA, Role::EMPLOYE);
        $folderA = Folder::query()->create(['nom' => 'Achats', 'created_by' => $userA->id]);
        $documentA = Document::query()->create(['folder_id' => $folderA->id, 'nom' => 'secret.pdf', 'auteur_id' => $userA->id, 'proprietaire_id' => $userA->id]);

        $companyB = $this->createCompany('B');
        $userB = $this->actingAsCompanyUser($companyB, Role::EMPLOYE);
        Sanctum::actingAs($userB);

        $this->getJson("/api/v1/documents/{$documentA->id}")->assertNotFound();
    }
}
