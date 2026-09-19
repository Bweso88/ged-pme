<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Domain\Documents\Services\DocumentDiffService;
use App\Livewire\Documents\Show;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class VersionDiffTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_diff_service_reports_added_and_removed_lines(): void
    {
        [, $v1, $v2] = $this->makeDocumentWithTwoVersions("ligne 1\nligne 2\n", "ligne 1\nligne 3\n");

        $diff = app(DocumentDiffService::class)->diff($v1, $v2);

        $this->assertStringContainsString('-ligne 2', $diff);
        $this->assertStringContainsString('+ligne 3', $diff);
    }

    public function test_diff_is_unavailable_when_neither_version_has_ocr_text(): void
    {
        [, $v1, $v2] = $this->makeDocumentWithTwoVersions(null, null);

        $this->assertFalse(app(DocumentDiffService::class)->canCompare($v1, $v2));
    }

    public function test_the_document_screen_exposes_the_diff_between_two_selected_versions(): void
    {
        [$document, $v1, $v2] = $this->makeDocumentWithTwoVersions("bonjour\n", "au revoir\n");

        Livewire::test(Show::class, ['document' => $document])
            ->set('compareFromVersionId', $v1->id)
            ->set('compareToVersionId', $v2->id)
            ->call('compareVersions')
            ->assertSet('diffUnavailable', false)
            ->assertSee('au revoir');
    }

    /** @return array{0: Document, 1: DocumentVersion, 2: DocumentVersion} */
    private function makeDocumentWithTwoVersions(?string $texteV1, ?string $texteV2): array
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        $document = Document::query()->create([
            'folder_id' => $folder->id,
            'nom' => 'note.txt',
            'statut' => Document::STATUT_BROUILLON,
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        $v1 = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'numero_version' => 1,
            'storage_path' => 'tenants/test/documents/v1.txt',
            'taille_octets' => 10,
            'hash_sha256' => str_repeat('a', 64),
            'mime_type' => 'text/plain',
            'texte_ocr' => $texteV1,
            'ocr_statut' => DocumentVersion::OCR_TERMINE,
            'auteur_id' => $user->id,
        ]);

        $v2 = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'numero_version' => 2,
            'storage_path' => 'tenants/test/documents/v2.txt',
            'taille_octets' => 10,
            'hash_sha256' => str_repeat('b', 64),
            'mime_type' => 'text/plain',
            'texte_ocr' => $texteV2,
            'ocr_statut' => DocumentVersion::OCR_TERMINE,
            'auteur_id' => $user->id,
        ]);

        $document->forceFill(['version_courante_id' => $v2->id])->save();

        return [$document, $v1, $v2];
    }
}
