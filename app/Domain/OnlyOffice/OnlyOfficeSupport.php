<?php

declare(strict_types=1);

namespace App\Domain\OnlyOffice;

use InvalidArgumentException;

/**
 * Formats éditables via OnlyOffice, restreints à l'intersection avec la liste blanche
 * d'extensions déjà autorisées à l'upload (voir DocumentUploadService::ALLOWED_EXTENSIONS).
 */
final class OnlyOfficeSupport
{
    private const EDITABLE_EXTENSIONS = ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

    public static function isEditable(string $extension): bool
    {
        return in_array(strtolower($extension), self::EDITABLE_EXTENSIONS, true);
    }

    public static function documentType(string $extension): string
    {
        return match (strtolower($extension)) {
            'doc', 'docx', 'txt' => 'word',
            'xls', 'xlsx' => 'cell',
            'ppt', 'pptx' => 'slide',
            default => throw new InvalidArgumentException("Extension non éditable via OnlyOffice : {$extension}"),
        };
    }
}
