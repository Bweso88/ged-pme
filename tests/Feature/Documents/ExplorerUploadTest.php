<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Livewire\Documents\Explorer;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class ExplorerUploadTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_a_file_rejected_by_the_upload_service_shows_a_visible_error(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        // Un fichier dont le contenu ne correspond pas à l'extension declaree (voir
        // DocumentUploadService::mimeMatchesExtension) : rejete avec une erreur sous la cle "file".
        $file = UploadedFile::fake()->create('facture.docx', 10)->mimeType('application/x-msdownload');

        Livewire::test(Explorer::class, ['folder' => $folder])
            ->set('uploads', [$file])
            ->assertHasErrors('file');
    }

    public function test_a_valid_word_document_is_imported_successfully(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        $file = UploadedFile::fake()->create(
            'facture.docx',
            10,
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );

        Livewire::test(Explorer::class, ['folder' => $folder])
            ->set('uploads', [$file])
            ->assertHasNoErrors();

        $this->assertSame(1, $folder->documents()->count());
    }
}
