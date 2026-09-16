<?php

declare(strict_types=1);

namespace App\Domain\Ocr\Engines;

use App\Domain\Ocr\OcrEngine;

/** Utilisé quand GED_OCR_ENGINE=null ou en environnement de test : aucune extraction. */
class NullOcrEngine implements OcrEngine
{
    public function extractText(string $absoluteFilePath, string $mimeType): ?string
    {
        return null;
    }
}
