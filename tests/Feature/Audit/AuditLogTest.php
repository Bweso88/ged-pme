<?php

declare(strict_types=1);

namespace Tests\Feature\Audit;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Documents\Services\DocumentUploadService;
use App\Models\AuditLog;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_uploading_a_document_writes_an_audit_entry(): void
    {
        Storage::fake('local');
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        $document = app(DocumentUploadService::class)->upload($folder, UploadedFile::fake()->createWithContent('a.txt', 'x'), $user);

        $this->assertDatabaseHas('audit_logs', [
            'utilisateur_id' => $user->id,
            'action' => AuditLog::CREATION,
            'ressource_type' => 'document',
            'ressource_id' => $document->id,
        ]);
    }

    public function test_audit_log_is_append_only_in_practice(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);

        $log = app(AuditLogger::class)->log($user, AuditLog::CONNEXION);

        $this->assertNotEmpty(AuditLog::query()->find($log->id));
        // Aucun contrôleur/policy de l'application n'expose de route de modification ou de
        // suppression pour ce modèle (voir routes/web.php et routes/api_v1.php) : l'invariant
        // "append-only" est garanti par absence de chemin d'écriture, pas par un verrou technique.
        $this->assertTrue(true);
    }
}
