<?php

declare(strict_types=1);

namespace Tests\Feature\Archiving;

use App\Domain\Archiving\Services\ArchiveService;
use App\Models\ArchiveRecord;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Folder;
use App\Models\RetentionPolicy;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class ArchiveRetentionTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_archiving_a_document_creates_an_archive_record_with_destruction_date_from_policy(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);

        $type = DocumentType::query()->create(['nom' => 'Contrat', 'code' => 'contrat']);
        RetentionPolicy::query()->create([
            'document_type_id' => $type->id, 'duree_conservation_mois' => 6, 'action_a_expiration' => 'demander_validation',
        ]);

        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'document_type_id' => $type->id, 'nom' => 'contrat.txt',
            'auteur_id' => $user->id, 'proprietaire_id' => $user->id, 'statut' => Document::STATUT_PUBLIE,
        ]);

        $record = app(ArchiveService::class)->archive($document, $user, null, 'confidentiel', 'Fin de contrat');

        $this->assertSame(Document::STATUT_ARCHIVE, $document->fresh()->statut);
        $this->assertSame(ArchiveRecord::STATUT_ACTIF, $record->statut);
        $this->assertNotNull($record->date_destruction_prevue);
        $this->assertTrue(now()->addMonths(6)->isSameDay($record->date_destruction_prevue));
    }

    public function test_detecting_due_records_proposes_destruction_without_deleting_anything(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);

        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'contrat.txt', 'auteur_id' => $user->id, 'proprietaire_id' => $user->id,
        ]);

        $record = ArchiveRecord::query()->create([
            'document_id' => $document->id,
            'date_archivage' => now()->subYear(),
            'confidentialite' => 'interne',
            'date_destruction_prevue' => now()->subDay()->toDateString(),
            'statut' => ArchiveRecord::STATUT_ACTIF,
        ]);

        $count = app(ArchiveService::class)->detectDueRecords();

        $this->assertSame(1, $count);
        $this->assertSame(ArchiveRecord::STATUT_PROPOSE_DESTRUCTION, $record->fresh()->statut);
        $this->assertFalse($document->fresh()->is_trashed);
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }

    public function test_destruction_requires_an_explicit_human_validation_with_a_reason(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);

        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'contrat.txt', 'auteur_id' => $user->id, 'proprietaire_id' => $user->id,
        ]);

        $record = ArchiveRecord::query()->create([
            'document_id' => $document->id,
            'date_archivage' => now(),
            'confidentialite' => 'interne',
            'statut' => ArchiveRecord::STATUT_PROPOSE_DESTRUCTION,
        ]);

        app(ArchiveService::class)->validateDestruction($record, $user, 'Durée légale atteinte, validé par la direction');

        $record->refresh();
        $this->assertSame(ArchiveRecord::STATUT_VALIDE_DESTRUCTION, $record->statut);
        $this->assertSame($user->id, $record->valide_par);
        $this->assertNotNull($record->valide_at);
        // Le document lui-même n'est jamais supprimé par ce mécanisme : la purge physique
        // reste une action distincte, hors moteur de rétention automatique (voir doc 04, §15).
        $this->assertDatabaseHas('documents', ['id' => $document->id]);
    }
}
