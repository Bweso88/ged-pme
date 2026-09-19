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

    'antivirus' => [
        // "clamav" (scan réel via clamdscan) ou "null" (aucun scan, non recommandé en production)
        'driver' => env('GED_ANTIVIRUS_DRIVER', 'null'),
        'binary' => env('GED_ANTIVIRUS_BINARY', '/usr/bin/clamdscan'),
    ],

    // Capture email : dépose automatiquement les pièces jointes des emails non lus d'une boîte
    // IMAP dédiée dans un dossier "à classer". Désactivée par défaut, prête à activer dès qu'une
    // adresse dédiée existe (voir docs/00-installation-debutant.md).
    'email_capture' => [
        'enabled' => (bool) env('GED_EMAIL_CAPTURE_ENABLED', false),
        'host' => env('GED_EMAIL_CAPTURE_HOST'),
        'port' => (int) env('GED_EMAIL_CAPTURE_PORT', 993),
        'encryption' => env('GED_EMAIL_CAPTURE_ENCRYPTION', 'ssl'), // "ssl", "tls" ou false
        'validate_cert' => (bool) env('GED_EMAIL_CAPTURE_VALIDATE_CERT', true),
        'username' => env('GED_EMAIL_CAPTURE_USERNAME'),
        'password' => env('GED_EMAIL_CAPTURE_PASSWORD'),
        'mailbox' => env('GED_EMAIL_CAPTURE_MAILBOX', 'INBOX'),
        // UUID du dossier YOSEFA "à classer" et de l'utilisateur technique au nom duquel les
        // pièces jointes sont déposées (visible dans l'écran Administration > Utilisateurs).
        'target_folder_id' => env('GED_EMAIL_CAPTURE_TARGET_FOLDER_ID'),
        'uploader_user_id' => env('GED_EMAIL_CAPTURE_UPLOADER_USER_ID'),
    ],

    // Signature électronique (voir doc 04, §13.3) : "yousign" ou "null" (désactivée, défaut).
    // Distincte de la validation de workflow ("Approuver"), qui n'a aucune valeur juridique de
    // signature.
    'signature' => [
        'driver' => env('GED_SIGNATURE_DRIVER', 'null'),
        'yousign' => [
            'api_key' => env('GED_SIGNATURE_YOUSIGN_API_KEY'),
            'base_url' => env('GED_SIGNATURE_YOUSIGN_BASE_URL', 'https://api-sandbox.yousign.app/v3'),
        ],
    ],

    // Édition Office en ligne (Word/Excel/PowerPoint dans le navigateur) via un serveur OnlyOffice
    // Document Server séparé (voir docker/onlyoffice/docker-compose.yml). Désactivée par défaut.
    'onlyoffice' => [
        'enabled' => (bool) env('GED_ONLYOFFICE_ENABLED', false),
        // URL du Document Server telle que joignable depuis le NAVIGATEUR de l'utilisateur.
        'document_server_url' => env('GED_ONLYOFFICE_DOCUMENT_SERVER_URL'),
        // Signature des configurations d'éditeur et vérification des rappels (fortement
        // recommandé en production — voir JWT_SECRET dans docker/onlyoffice/docker-compose.yml).
        'jwt_secret' => env('GED_ONLYOFFICE_JWT_SECRET'),
        // URL à laquelle LE SERVEUR OnlyOffice (pas le navigateur) peut atteindre YOSEFA, si
        // différente de APP_URL (ex. les deux conteneurs communiquent sur un réseau Docker
        // interne alors que APP_URL est l'URL publique). Laisser vide si elles sont identiques.
        'internal_app_url' => env('GED_ONLYOFFICE_INTERNAL_APP_URL'),
    ],
];
