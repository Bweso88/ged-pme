<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Storage;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;

/**
 * Diff textuel entre deux versions d'un document. Pour un PDF ou une image scannée, seul le
 * texte OCR (voir App\Domain\Ocr) est comparable. Pour un fichier déjà textuel (.txt), l'OCR ne
 * s'exécute jamais (voir DocumentUploadService::OCR_ELIGIBLE_MIME_TYPES) : on lit alors
 * directement le contenu stocké, qui EST le texte.
 */
class DocumentDiffService
{
    private const PLAIN_TEXT_MIME_TYPES = ['text/plain'];

    public function canCompare(DocumentVersion $from, DocumentVersion $to): bool
    {
        return $this->comparableText($from) !== null || $this->comparableText($to) !== null;
    }

    /** @return string Diff au format unifié (lignes préfixées +/-), vide si aucune différence */
    public function diff(DocumentVersion $from, DocumentVersion $to): string
    {
        $builder = new UnifiedDiffOutputBuilder("--- Version {$from->numero_version}\n+++ Version {$to->numero_version}\n");
        $differ = new Differ($builder);

        return $differ->diff((string) $this->comparableText($from), (string) $this->comparableText($to));
    }

    private function comparableText(DocumentVersion $version): ?string
    {
        if (trim((string) $version->texte_ocr) !== '') {
            return $version->texte_ocr;
        }

        if (in_array($version->mime_type, self::PLAIN_TEXT_MIME_TYPES, true)) {
            $disk = Storage::disk(config('ged.storage_disk'));

            return $disk->exists($version->storage_path) ? $disk->get($version->storage_path) : null;
        }

        return null;
    }
}
