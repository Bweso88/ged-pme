<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Models\DocumentVersion;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * Diff textuel entre deux versions d'un document, basé sur leur texte OCR (voir
 * App\Domain\Ocr — seul contenu comparable de façon fiable quel que soit le format d'origine :
 * PDF, image scannée, .txt...). Sans texte OCR des deux côtés, aucune comparaison n'est possible.
 */
class DocumentDiffService
{
    public function canCompare(DocumentVersion $from, DocumentVersion $to): bool
    {
        return trim((string) $from->texte_ocr) !== '' || trim((string) $to->texte_ocr) !== '';
    }

    /** @return string Diff au format unifié (lignes préfixées +/-), vide si aucune différence */
    public function diff(DocumentVersion $from, DocumentVersion $to): string
    {
        $builder = new UnifiedDiffOutputBuilder("--- Version {$from->numero_version}\n+++ Version {$to->numero_version}\n");
        $differ = new Differ($builder);

        return $differ->diff((string) $from->texte_ocr, (string) $to->texte_ocr);
    }
}
