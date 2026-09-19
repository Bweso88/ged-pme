<?php

declare(strict_types=1);

namespace App\Domain\Security\Scanners;

use App\Domain\Security\AntivirusScanFailedException;
use App\Domain\Security\AntivirusScanner;
use App\Domain\Security\AntivirusScanResult;
use Illuminate\Support\Facades\Process;

/**
 * Scan réel via clamdscan (client léger qui interroge le démon clamd, plus rapide que clamscan
 * qui recharge la base de signatures à chaque appel). Codes de sortie clamdscan : 0 = propre,
 * 1 = menace détectée, 2 = erreur (démon injoignable, fichier illisible...).
 */
class ClamAvAntivirusScanner implements AntivirusScanner
{
    public function __construct(private readonly string $binary) {}

    public function scan(string $filePath): AntivirusScanResult
    {
        $result = Process::run([$this->binary, '--no-summary', $filePath]);

        if ($result->successful()) {
            return AntivirusScanResult::clean();
        }

        if ($result->exitCode() === 1) {
            return AntivirusScanResult::infected($this->parseThreatName($result->output()));
        }

        throw new AntivirusScanFailedException(
            "clamdscan a échoué (code {$result->exitCode()}) : ".trim($result->errorOutput().' '.$result->output())
        );
    }

    private function parseThreatName(string $output): string
    {
        if (preg_match('/:\s*(.+?)\s+FOUND\b/', $output, $matches) === 1) {
            return $matches[1];
        }

        return 'menace non identifiée';
    }
}
