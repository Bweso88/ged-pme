<?php

declare(strict_types=1);

namespace App\Domain\Ocr\Jobs;

use App\Domain\Ocr\OcrEngine;
use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Extraction OCR asynchrone (voir doc 01, §9) : ne bloque jamais l'upload. Le texte extrait
 * alimente l'index plein texte PostgreSQL utilisé par la recherche avancée.
 */
class ProcessDocumentOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $documentVersionId) {}

    public function handle(OcrEngine $ocrEngine): void
    {
        $version = DocumentVersion::withoutTenantScope()->find($this->documentVersionId);

        if ($version === null) {
            return;
        }

        $disk = Storage::disk(config('ged.storage_disk'));

        // Copie locale temporaire : fonctionne quel que soit le disque (local ou S3-compatible),
        // conformément à l'abstraction de stockage documentée (doc 02, §10).
        $extension = pathinfo($version->storage_path, PATHINFO_EXTENSION);
        $tmpPath = tempnam(sys_get_temp_dir(), 'ged_ocr_').'.'.$extension;
        file_put_contents($tmpPath, $disk->get($version->storage_path));

        try {
            $text = $ocrEngine->extractText($tmpPath, $version->mime_type);
        } finally {
            @unlink($tmpPath);
        }

        $version->forceFill([
            'texte_ocr' => $text,
            'ocr_statut' => $text !== null ? DocumentVersion::OCR_TERMINE : DocumentVersion::OCR_ECHEC,
        ])->save();
    }
}
