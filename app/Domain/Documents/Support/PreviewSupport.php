<?php

declare(strict_types=1);

namespace App\Domain\Documents\Support;

/**
 * Types MIME que l'aperçu en ligne (PreviewController) sait afficher lui-même — image ou PDF,
 * rendus nativement par le navigateur/l'app, sans dépendre d'un logiciel tiers. Les autres
 * types (Word, Excel...) ne sont accessibles qu'en téléchargement (droit séparé), ou en
 * édition en ligne via OnlyOffice pour les personnes autorisées.
 */
final class PreviewSupport
{
    private const PREVIEWABLE_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
    ];

    public static function isPreviewable(?string $mimeType): bool
    {
        return $mimeType !== null && in_array($mimeType, self::PREVIEWABLE_MIME_TYPES, true);
    }
}
