<?php

declare(strict_types=1);

namespace App\Domain\Security\Scanners;

use App\Domain\Security\AntivirusScanner;
use App\Domain\Security\AntivirusScanResult;

/**
 * Implémentation par défaut, documentée comme telle : ne scanne rien, toujours "propre".
 * À remplacer par ClamAvAntivirusScanner (GED_ANTIVIRUS_DRIVER=clamav) pour un scan réel.
 */
class NullAntivirusScanner implements AntivirusScanner
{
    public function scan(string $filePath): AntivirusScanResult
    {
        return AntivirusScanResult::clean();
    }
}
