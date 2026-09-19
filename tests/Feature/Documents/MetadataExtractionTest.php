<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Domain\Documents\Services\MetadataExtractionService;
use App\Domain\Documents\Services\MetadataService;
use App\Models\Document;
use App\Models\DocumentMetadataSuggestion;
use App\Models\DocumentType;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\MetadataField;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class MetadataExtractionTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_it_proposes_a_value_matching_the_configured_pattern(): void
    {
        [$document, $version] = $this->makeDocumentWithField(
            pattern: 'Facture\s*n[°o]\s*(\S+)',
            texteOcr: "FACTURE\nFacture n°2026-042\nMontant : 150,00 €",
        );

        app(MetadataExtractionService::class)->suggestFor($version->fresh());

        $suggestion = DocumentMetadataSuggestion::query()->where('document_id', $document->id)->sole();
        $this->assertSame('2026-042', $suggestion->valeur_proposee);
        $this->assertSame(DocumentMetadataSuggestion::STATUT_EN_ATTENTE, $suggestion->statut);
    }

    public function test_it_does_not_create_a_suggestion_when_the_pattern_does_not_match(): void
    {
        [$document, $version] = $this->makeDocumentWithField(
            pattern: 'Facture\s*n[°o]\s*(\S+)',
            texteOcr: 'Un document sans numéro de facture reconnaissable.',
        );

        app(MetadataExtractionService::class)->suggestFor($version->fresh());

        $this->assertSame(0, DocumentMetadataSuggestion::query()->where('document_id', $document->id)->count());
    }

    public function test_accepting_a_suggestion_writes_the_real_metadata_value_and_marks_it_accepted(): void
    {
        [$document, $version] = $this->makeDocumentWithField(
            pattern: 'Facture\s*n[°o]\s*(\S+)',
            texteOcr: 'Facture n°2026-042',
        );
        app(MetadataExtractionService::class)->suggestFor($version->fresh());
        $suggestion = DocumentMetadataSuggestion::query()->where('document_id', $document->id)->sole();

        app(MetadataService::class)->acceptSuggestion($suggestion);

        $this->assertSame('2026-042', app(MetadataService::class)->valuesFor($document)['numero_facture']);
        $this->assertSame(DocumentMetadataSuggestion::STATUT_ACCEPTEE, $suggestion->fresh()->statut);
    }

    public function test_a_resolved_suggestion_is_not_overwritten_by_a_later_extraction_run(): void
    {
        [$document, $version] = $this->makeDocumentWithField(
            pattern: 'Facture\s*n[°o]\s*(\S+)',
            texteOcr: 'Facture n°2026-042',
        );
        app(MetadataExtractionService::class)->suggestFor($version->fresh());
        $suggestion = DocumentMetadataSuggestion::query()->where('document_id', $document->id)->sole();
        app(MetadataService::class)->rejectSuggestion($suggestion);

        // Une nouvelle version dont le texte OCR matcherait une valeur différente ne doit pas
        // rouvrir une suggestion déjà tranchée par un humain.
        $version->forceFill(['texte_ocr' => 'Facture n°2026-999'])->save();
        app(MetadataExtractionService::class)->suggestFor($version->fresh());

        $this->assertSame(DocumentMetadataSuggestion::STATUT_REJETEE, $suggestion->fresh()->statut);
        $this->assertSame('2026-042', $suggestion->fresh()->valeur_proposee);
    }

    /** @return array{0: Document, 1: DocumentVersion} */
    private function makeDocumentWithField(string $pattern, string $texteOcr): array
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        $type = DocumentType::query()->create(['nom' => 'Facture', 'code' => 'facture']);
        MetadataField::query()->create([
            'document_type_id' => $type->id,
            'code' => 'numero_facture',
            'label' => 'Numéro de facture',
            'type' => MetadataField::TYPE_TEXTE,
            'extraction_pattern' => $pattern,
        ]);

        $document = Document::query()->create([
            'folder_id' => $folder->id,
            'document_type_id' => $type->id,
            'nom' => 'facture.pdf',
            'statut' => Document::STATUT_BROUILLON,
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'numero_version' => 1,
            'storage_path' => 'tenants/test/documents/facture.pdf',
            'taille_octets' => 10,
            'hash_sha256' => str_repeat('a', 64),
            'mime_type' => 'application/pdf',
            'texte_ocr' => $texteOcr,
            'ocr_statut' => DocumentVersion::OCR_TERMINE,
            'auteur_id' => $user->id,
        ]);

        return [$document, $version];
    }
}
