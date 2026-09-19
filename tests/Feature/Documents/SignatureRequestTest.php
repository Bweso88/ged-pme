<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Domain\Documents\Services\DocumentUploadService;
use App\Domain\Signature\Providers\NullSignatureProvider;
use App\Domain\Signature\Signataire;
use App\Domain\Signature\SignatureProvider;
use App\Domain\Signature\SignatureProviderException;
use App\Domain\Signature\SignatureService;
use App\Livewire\Documents\Show;
use App\Models\Folder;
use App\Models\Role;
use App\Models\SignatureRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class SignatureRequestTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_the_default_null_provider_refuses_to_send(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Contrats', 'created_by' => $user->id]);
        $document = app(DocumentUploadService::class)->upload($folder, UploadedFile::fake()->createWithContent('bail.pdf', 'contenu'), $user);

        $this->expectException(SignatureProviderException::class);
        app(SignatureService::class)->requestSignature($document, [new Signataire('Jean Dupont', 'jean@exemple.fr')], $user);
    }

    public function test_a_failed_send_records_an_error_status(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Contrats', 'created_by' => $user->id]);
        $document = app(DocumentUploadService::class)->upload($folder, UploadedFile::fake()->createWithContent('bail.pdf', 'contenu'), $user);

        try {
            app(SignatureService::class)->requestSignature($document, [new Signataire('Jean Dupont', 'jean@exemple.fr')], $user);
        } catch (SignatureProviderException) {
            // attendu
        }

        $record = SignatureRequest::query()->where('document_id', $document->id)->sole();
        $this->assertSame(SignatureRequest::STATUT_ERREUR, $record->statut);
    }

    public function test_a_successful_send_records_the_external_id(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Contrats', 'created_by' => $user->id]);
        $document = app(DocumentUploadService::class)->upload($folder, UploadedFile::fake()->createWithContent('bail.pdf', 'contenu'), $user);

        $this->app->bind(SignatureProvider::class, fn () => new class implements SignatureProvider
        {
            public function send(\App\Models\Document $document, array $signataires): string
            {
                return 'ext-123';
            }

            public function downloadSignedDocument(string $externalId): ?string
            {
                return null;
            }
        });

        $record = app(SignatureService::class)->requestSignature($document, [new Signataire('Jean Dupont', 'jean@exemple.fr')], $user);

        $this->assertSame('ext-123', $record->external_id);
        $this->assertSame(SignatureRequest::STATUT_ENVOYE, $record->statut);
    }

    public function test_refresh_status_attaches_the_signed_document_as_a_new_version(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Contrats', 'created_by' => $user->id]);
        $document = app(DocumentUploadService::class)->upload($folder, UploadedFile::fake()->createWithContent('bail.txt', 'contenu'), $user);

        $signedTmpPath = tempnam(sys_get_temp_dir(), 'signed_').'.txt';
        file_put_contents($signedTmpPath, 'contenu signé');

        $this->app->bind(SignatureProvider::class, fn () => new class($signedTmpPath) implements SignatureProvider
        {
            public function __construct(private string $signedTmpPath) {}

            public function send(\App\Models\Document $document, array $signataires): string
            {
                return 'ext-123';
            }

            public function downloadSignedDocument(string $externalId): ?string
            {
                return $this->signedTmpPath;
            }
        });

        $record = app(SignatureService::class)->requestSignature($document, [new Signataire('Jean Dupont', 'jean@exemple.fr')], $user);
        app(SignatureService::class)->refreshStatus($record);

        $this->assertSame(SignatureRequest::STATUT_SIGNE, $record->fresh()->statut);
        $this->assertSame(2, $document->fresh()->versions()->count());
    }

    public function test_the_document_screen_hides_the_signature_panel_without_permission(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::LECTEUR);
        $folder = Folder::query()->create(['nom' => 'Contrats', 'created_by' => $user->id]);
        $document = app(DocumentUploadService::class)->upload($folder, UploadedFile::fake()->createWithContent('bail.pdf', 'contenu'), $user);

        Livewire::test(Show::class, ['document' => $document])
            ->assertDontSee('Signature électronique');
    }
}
