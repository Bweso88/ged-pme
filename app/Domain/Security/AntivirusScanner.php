<?php

declare(strict_types=1);

namespace App\Domain\Security;

/**
 * Point d'extension antivirus (voir doc 04, §11.2). Implémentation par défaut : NullAntivirusScanner
 * (no-op, toujours "propre"), branchable en ClamAvAntivirusScanner par configuration.
 */
interface AntivirusScanner
{
    /** @throws AntivirusScanFailedException si le moteur n'a pas pu rendre de verdict */
    public function scan(string $filePath): AntivirusScanResult;
}
