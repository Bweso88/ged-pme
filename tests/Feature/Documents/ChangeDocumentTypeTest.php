<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Livewire\Documents\Show;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class ChangeDocumentTypeTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_a_document_created_without_a_type_can_be_assigned_one_afterwards(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $type = DocumentType::query()->create(['nom' => 'Facture', 'code' => 'facture']);

        $document = Document::query()->create([
            'folder_id' => $folder->id,
            'nom' => 'note.txt',
            'statut' => Document::STATUT_BROUILLON,
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        Livewire::test(Show::class, ['document' => $document])
            ->set('documentTypeId', $type->id)
            ->call('changeDocumentType')
            ->assertHasNoErrors();

        $this->assertSame($type->id, $document->refresh()->document_type_id);
    }
}
