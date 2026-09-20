<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Livewire\Documents\Show;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\Folder;
use App\Models\MetadataField;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/**
 * Enregistrer les métadonnées ne provoquait aucun retour visible en cas de succès : un
 * utilisateur qui corrigeait une erreur de validation et cliquait à nouveau ne pouvait pas
 * savoir si son second clic avait fonctionné.
 */
class MetadataSaveConfirmationTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_a_successful_save_shows_a_confirmation_message(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $type = DocumentType::query()->create(['nom' => 'Facture', 'code' => 'facture']);
        MetadataField::query()->create(['document_type_id' => $type->id, 'code' => 'titre', 'label' => 'Titre', 'type' => MetadataField::TYPE_TEXTE, 'obligatoire' => true]);

        $document = Document::query()->create([
            'folder_id' => $folder->id,
            'document_type_id' => $type->id,
            'nom' => 'facture.pdf',
            'statut' => Document::STATUT_BROUILLON,
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        Livewire::test(Show::class, ['document' => $document])
            ->set('metadata.titre', 'Facture test')
            ->call('saveMetadata')
            ->assertHasNoErrors()
            ->assertSet('metadataSaved', true)
            ->assertSee('Métadonnées enregistrées.');
    }

    public function test_editing_a_field_again_hides_the_previous_confirmation(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $type = DocumentType::query()->create(['nom' => 'Facture', 'code' => 'facture']);
        MetadataField::query()->create(['document_type_id' => $type->id, 'code' => 'titre', 'label' => 'Titre', 'type' => MetadataField::TYPE_TEXTE, 'obligatoire' => true]);

        $document = Document::query()->create([
            'folder_id' => $folder->id,
            'document_type_id' => $type->id,
            'nom' => 'facture.pdf',
            'statut' => Document::STATUT_BROUILLON,
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        Livewire::test(Show::class, ['document' => $document])
            ->set('metadata.titre', 'Facture test')
            ->call('saveMetadata')
            ->assertSet('metadataSaved', true)
            ->set('metadata.titre', 'Facture test modifiée')
            ->assertSet('metadataSaved', false);
    }

    public function test_a_validation_failure_never_shows_the_confirmation(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $type = DocumentType::query()->create(['nom' => 'Facture', 'code' => 'facture']);
        MetadataField::query()->create(['document_type_id' => $type->id, 'code' => 'titre', 'label' => 'Titre', 'type' => MetadataField::TYPE_TEXTE, 'obligatoire' => true]);

        $document = Document::query()->create([
            'folder_id' => $folder->id,
            'document_type_id' => $type->id,
            'nom' => 'facture.pdf',
            'statut' => Document::STATUT_BROUILLON,
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        Livewire::test(Show::class, ['document' => $document])
            ->call('saveMetadata')
            ->assertHasErrors('metadata.titre')
            ->assertSet('metadataSaved', false)
            ->assertDontSee('Métadonnées enregistrées.');
    }
}
