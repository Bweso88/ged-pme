<?php

declare(strict_types=1);

namespace App\Domain\Ocr\Engines;

use App\Domain\Ocr\OcrEngine;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class TesseractOcrEngine implements OcrEngine
{
    public function __construct(private readonly string $binaryPath) {}

    public function extractText(string $absoluteFilePath, string $mimeType): ?string
    {
        if (! is_executable($this->binaryPath)) {
            Log::warning('OCR: binaire Tesseract introuvable, extraction ignorée.', ['binary' => $this->binaryPath]);

            return null;
        }

        // "stdout" comme préfixe de sortie demande à tesseract d'écrire directement sur la sortie standard.
        $process = new Process([$this->binaryPath, $absoluteFilePath, 'stdout', '-l', 'fra']);
        $process->setTimeout(120);

        try {
            $process->run();

            if (! $process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            return trim($process->getOutput()) ?: null;
        } catch (\Throwable $e) {
            Log::warning('OCR: échec de l\'extraction Tesseract.', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
