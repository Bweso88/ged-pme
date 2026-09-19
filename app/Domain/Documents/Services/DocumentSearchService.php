<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Recherche avancée (voir doc ged-pme/docs/01-vision-produit.md, §8) : filtres combinés sur
 * nom/type/auteur/service/date/référence/statut/métadonnées, plus recherche plein texte sur
 * le contenu extrait par OCR (index PostgreSQL GIN sur document_versions.texte_ocr).
 */
class DocumentSearchService
{
    /** @param array<string, mixed> $filters */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = Document::query()->with(['folder', 'documentType', 'auteur']);

        if (! empty($filters['q'])) {
            $this->applyFullTextSearch($query, (string) $filters['q']);
        }

        if (! empty($filters['document_type_id'])) {
            $query->where('document_type_id', $filters['document_type_id']);
        }

        if (! empty($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (! empty($filters['auteur_id'])) {
            $query->where('auteur_id', $filters['auteur_id']);
        }

        if (! empty($filters['reference'])) {
            $query->where('reference', 'ilike', '%'.$filters['reference'].'%');
        }

        if (! empty($filters['date_from'])) {
            $query->where('date_document', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('date_document', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('updated_at')->paginate(20);
    }

    private function applyFullTextSearch(Builder $query, string $terms): void
    {
        $query->where(function (Builder $q) use ($terms) {
            $q->where('nom', 'ilike', "%{$terms}%")
                ->orWhereRaw(
                    "to_tsvector('french', coalesce(cast(mots_cles as text), '')) @@ plainto_tsquery('french', ?)",
                    [$terms]
                )
                ->orWhereIn('id', function ($sub) use ($terms) {
                    $sub->select('document_id')
                        ->from('document_versions')
                        ->whereRaw("to_tsvector('french', coalesce(texte_ocr, '')) @@ plainto_tsquery('french', ?)", [$terms]);
                });
        });
    }
}
