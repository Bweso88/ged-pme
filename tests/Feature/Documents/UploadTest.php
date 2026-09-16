<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Domain\Documents\Services\DocumentUploadService;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_upload_creates_document_with_first_version(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        $file = UploadedFile::fake()->createWithContent('facture.txt', "Facture n°42\n");

        $document = app(DocumentUploadService::class)->upload($folder, $file, $user);

        $this->assertSame('facture.txt', $document->nom);
        $this->assertSame(1, $document->versions()->count());
        $this->assertNotNull($document->version_courante_id);
        Storage::disk('local')->assertExists($document->versionCourante->storage_path);
    }

    public function test_upload_rejects_disallowed_extension(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        $file = UploadedFile::fake()->create('script.exe', 10);

        $this->expectException(ValidationException::class);
        app(DocumentUploadService::class)->upload($folder, $file, $user);
    }

    public function test_upload_rejects_mime_type_not_matching_extension(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        // Un exécutable renommé en .pdf : le type MIME réel ne correspond pas à l'extension déclarée.
        $file = UploadedFile::fake()->create('faux.pdf', 10)->mimeType('application/x-msdownload');

        $this->expectException(ValidationException::class);
        app(DocumentUploadService::class)->upload($folder, $file, $user);
    }

    public function test_adding_a_new_version_never_overwrites_the_previous_one(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        $service = app(DocumentUploadService::class);
        $document = $service->upload($folder, UploadedFile::fake()->createWithContent('contrat.txt', 'v1'), $user);
        $service->addVersion($document, UploadedFile::fake()->createWithContent('contrat.txt', 'v2'), $user, 'mise à jour');

        $document->refresh();

        $this->assertSame(2, $document->versions()->count());
        $this->assertSame(2, $document->versionCourante->numero_version);
        $this->assertNotSame(
            $document->versions()->where('numero_version', 1)->first()->storage_path,
            $document->versions()->where('numero_version', 2)->first()->storage_path,
        );
    }
}
