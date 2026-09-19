<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Domain\Documents\Services\DocumentUploadService;
use App\Domain\Security\AntivirusScanFailedException;
use App\Domain\Security\AntivirusScanner;
use App\Domain\Security\AntivirusScanResult;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class AntivirusScanTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_upload_rejects_a_file_flagged_as_infected(): void
    {
        $this->app->bind(AntivirusScanner::class, fn () => new class implements AntivirusScanner
        {
            public function scan(string $filePath): AntivirusScanResult
            {
                return AntivirusScanResult::infected('Eicar-Test-Signature');
            }
        });

        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        $this->expectException(ValidationException::class);
        app(DocumentUploadService::class)->upload(
            $folder,
            UploadedFile::fake()->createWithContent('facture.txt', 'contenu'),
            $user,
        );
    }

    public function test_upload_is_refused_when_the_antivirus_engine_is_unreachable(): void
    {
        $this->app->bind(AntivirusScanner::class, fn () => new class implements AntivirusScanner
        {
            public function scan(string $filePath): AntivirusScanResult
            {
                throw new AntivirusScanFailedException('démon clamd injoignable');
            }
        });

        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        $this->expectException(ValidationException::class);
        app(DocumentUploadService::class)->upload(
            $folder,
            UploadedFile::fake()->createWithContent('facture.txt', 'contenu'),
            $user,
        );
    }

    public function test_upload_succeeds_for_a_clean_file(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        // Aucun binding explicite : le driver par défaut ("null") laisse passer, comme documenté.
        $document = app(DocumentUploadService::class)->upload(
            $folder,
            UploadedFile::fake()->createWithContent('facture.txt', 'contenu'),
            $user,
        );

        $this->assertNotNull($document->id);
    }
}
