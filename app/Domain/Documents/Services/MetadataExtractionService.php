<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Models\Document;
use App\Models\DocumentMetadataSuggestion;
use App\Models\DocumentVersion;
use App\Models\MetadataField;

/**
 * Propose des valeurs de métadonnées (numéro de facture, montant, date...) à partir du texte
 * OCR, via des règles (expressions régulières) configurées par l'administrateur sur chaque
 * champ. Ne remplit jamais document_metadata_values directement : la proposition doit être
 * acceptée explicitement par un utilisateur (voir MetadataService::acceptSuggestion).
 */
class MetadataExtractionService
{
    public function suggestFor(DocumentVersion $version): void
    {
        if ($version->texte_ocr === null || trim($version->texte_ocr) === '') {
            return;
        }

        $document = $version->document;
        $fields = $this->extractableFieldsFor($document);

        foreach ($fields as $field) {
            $value = $this->extractValue($field->extraction_pattern, $version->texte_ocr);

            if ($value === null) {
                continue;
            }

            $this->upsertSuggestion($document, $field, $value);
        }
    }

    /** @return \Illuminate\Support\Collection<int, MetadataField> */
    private function extractableFieldsFor(Document $document): \Illuminate\Support\Collection
    {
        return MetadataField::query()
            ->whereNotNull('extraction_pattern')
            ->where('extraction_pattern', '!=', '')
            ->where(function ($query) use ($document) {
                $query->whereNull('document_type_id')->orWhere('document_type_id', $document->document_type_id);
            })
            ->get();
    }

    private function extractValue(string $pattern, string $text): ?string
    {
        set_error_handler(static fn () => true);
        $matched = @preg_match('/'.str_replace('/', '\/', $pattern).'/u', $text, $matches);
        restore_error_handler();

        if ($matched !== 1) {
            return null;
        }

        $value = trim($matches[1] ?? $matches[0]);

        return $value === '' ? null : $value;
    }

    private function upsertSuggestion(Document $document, MetadataField $field, string $value): void
    {
        $existing = DocumentMetadataSuggestion::query()
            ->where('document_id', $document->id)
            ->where('metadata_field_id', $field->id)
            ->first();

        // Une suggestion déjà tranchée par un humain (acceptée ou rejetée) ne doit pas
        // réapparaître à chaque nouvelle version : seule une suggestion en attente est mise à jour.
        if ($existing !== null && $existing->statut !== DocumentMetadataSuggestion::STATUT_EN_ATTENTE) {
            return;
        }

        DocumentMetadataSuggestion::query()->updateOrCreate(
            ['document_id' => $document->id, 'metadata_field_id' => $field->id],
            [
                'company_id' => $document->company_id,
                'valeur_proposee' => $value,
                'statut' => DocumentMetadataSuggestion::STATUT_EN_ATTENTE,
            ],
        );
    }
}
