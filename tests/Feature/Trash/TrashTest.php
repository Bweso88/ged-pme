<?php

declare(strict_types=1);

namespace Tests\Feature\Trash;

use App\Domain\Documents\Services\TrashService;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class TrashTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_deleting_a_document_moves_it_to_trash_instead_of_deleting_it(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'doc.txt', 'auteur_id' => $user->id, 'proprietaire_id' => $user->id,
        ]);

        app(TrashService::class)->moveToTrash($document, $user);

        $this->assertTrue($document->fresh()->is_trashed);
        $this->assertSame(0, Document::query()->count(), 'Un document en corbeille ne doit plus apparaître dans les listes normales.');
        $this->assertSame(1, Document::onlyTrashed()->count());
    }

    public function test_a_trashed_document_can_be_restored(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'doc.txt', 'auteur_id' => $user->id, 'proprietaire_id' => $user->id,
        ]);

        $trashService = app(TrashService::class);
        $trashService->moveToTrash($document, $user);
        $trashService->restore($document->fresh(), $user);

        $this->assertFalse($document->fresh()->is_trashed);
        $this->assertSame(1, Document::query()->count());
    }

    public function test_force_delete_purges_physical_files_and_is_irreversible(): void
    {
        Storage::fake('local');
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'doc.txt', 'auteur_id' => $user->id, 'proprietaire_id' => $user->id,
        ]);
        $version = $document->versions()->create([
            'numero_version' => 1, 'storage_path' => 'tenants/x/documents/doc.txt', 'taille_octets' => 10,
            'hash_sha256' => str_repeat('a', 64), 'mime_type' => 'text/plain', 'auteur_id' => $user->id,
        ]);
        Storage::disk('local')->put($version->storage_path, 'contenu');

        app(TrashService::class)->forceDelete($document, $user);

        Storage::disk('local')->assertMissing($version->storage_path);
        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
    }

    public function test_only_admin_can_force_delete_a_document(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $employe = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $employe->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'doc.txt', 'auteur_id' => $employe->id, 'proprietaire_id' => $employe->id,
        ]);
        app(TrashService::class)->moveToTrash($document, $employe);

        $this->assertFalse($employe->hasPermission('document.delete_permanent'));
    }
}
