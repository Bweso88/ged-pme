<?php

declare(strict_types=1);

return [
    // "local" (déploiement PME mono-serveur) ou tout disque compatible S3 configuré dans config/filesystems.php
    'storage_disk' => env('GED_STORAGE_DISK', 'local'),

    'max_upload_mb' => (int) env('GED_MAX_UPLOAD_MB', 50),

    'ocr' => [
        // "tesseract" ou "null" (désactivé) — voir App\Domain\Ocr\OcrEngineManager
        'engine' => env('GED_OCR_ENGINE', 'tesseract'),
        'tesseract_binary' => env('GED_TESSERACT_BINARY', '/usr/bin/tesseract'),
    ],

    // Nombre de jours de conservation en corbeille avant qu'une purge soit proposée (jamais automatique, voir doc 04 §14.4)
    'trash_retention_days' => (int) env('GED_TRASH_RETENTION_DAYS', 30),
];
