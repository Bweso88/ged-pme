<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Models\Document;
use App\Models\DocumentMetadataSuggestion;
use App\Models\MetadataField;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Enregistre les valeurs de métadonnées d'un document en respectant les champs configurés
 * pour son type (voir doc 03, table metadata_fields / document_metadata_values).
 */
class MetadataService
{
    /** @param array<string, mixed> $values Clé = MetadataField->code */
    public function save(Document $document, array $values): void
    {
        $fields = $this->fieldsFor($document);

        foreach ($fields as $field) {
            if ($field->obligatoire && trim((string) ($values[$field->code] ?? '')) === '') {
                throw ValidationException::withMessages([
                    "metadata.{$field->code}" => "Le champ « {$field->label} » est obligatoire.",
                ]);
            }
        }

        foreach ($fields as $field) {
            if (! array_key_exists($field->code, $values)) {
                continue;
            }

            DB::table('document_metadata_values')->updateOrInsert(
                ['document_id' => $document->id, 'metadata_field_id' => $field->id],
                ['company_id' => $document->company_id, 'valeur' => (string) $values[$field->code]],
            );
        }
    }

    /** @return Collection<int, MetadataField> */
    public function fieldsFor(Document $document): Collection
    {
        return MetadataField::query()
            ->where(function ($query) use ($document) {
                $query->whereNull('document_type_id')->orWhere('document_type_id', $document->document_type_id);
            })
            ->orderBy('ordre')
            ->get();
    }

    /** @return array<string, string> Clé = code du champ */
    public function valuesFor(Document $document): array
    {
        return DB::table('document_metadata_values')
            ->join('metadata_fields', 'metadata_fields.id', '=', 'document_metadata_values.metadata_field_id')
            ->where('document_metadata_values.document_id', $document->id)
            ->pluck('document_metadata_values.valeur', 'metadata_fields.code')
            ->all();
    }

    /** @return Collection<int, DocumentMetadataSuggestion> */
    public function pendingSuggestionsFor(Document $document): Collection
    {
        return DocumentMetadataSuggestion::query()
            ->with('metadataField')
            ->where('document_id', $document->id)
            ->where('statut', DocumentMetadataSuggestion::STATUT_EN_ATTENTE)
            ->get();
    }

    public function acceptSuggestion(DocumentMetadataSuggestion $suggestion): void
    {
        DB::table('document_metadata_values')->updateOrInsert(
            ['document_id' => $suggestion->document_id, 'metadata_field_id' => $suggestion->metadata_field_id],
            ['company_id' => $suggestion->company_id, 'valeur' => $suggestion->valeur_proposee],
        );

        $suggestion->forceFill(['statut' => DocumentMetadataSuggestion::STATUT_ACCEPTEE])->save();
    }

    public function rejectSuggestion(DocumentMetadataSuggestion $suggestion): void
    {
        $suggestion->forceFill(['statut' => DocumentMetadataSuggestion::STATUT_REJETEE])->save();
    }
}
