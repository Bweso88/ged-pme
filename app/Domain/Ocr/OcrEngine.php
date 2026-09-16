<?php

declare(strict_types=1);

namespace App\Domain\Ocr;

/**
 * Abstraction du moteur OCR (voir doc ged-pme/docs/01-vision-produit.md, §9) : permet de
 * brancher Tesseract (par défaut, local) ou une API cloud (Google Vision, AWS Textract...)
 * sans changer le code appelant (App\Domain\Ocr\Jobs\ProcessDocumentOcr).
 */
interface OcrEngine
{
    /**
     * @return string|null Le texte extrait, ou null si l'extraction a échoué / n'est pas supportée.
     */
    public function extractText(string $absoluteFilePath, string $mimeType): ?string;
}
