<?php

declare(strict_types=1);

namespace App\Domain\Signature\Providers;

use App\Domain\Signature\SignatureProvider;
use App\Domain\Signature\SignatureProviderException;
use App\Models\Document;

/**
 * Implémentation par défaut, active tant que GED_SIGNATURE_DRIVER n'est pas configuré. Échoue
 * explicitement plutôt que de simuler un envoi : voir App\Domain\Signature\SignatureProvider.
 */
class NullSignatureProvider implements SignatureProvider
{
    public function send(Document $document, array $signataires): string
    {
        throw new SignatureProviderException(
            "Aucun prestataire de signature électronique n'est configuré (GED_SIGNATURE_DRIVER).",
        );
    }

    public function downloadSignedDocument(string $externalId): ?string
    {
        throw new SignatureProviderException(
            "Aucun prestataire de signature électronique n'est configuré (GED_SIGNATURE_DRIVER).",
        );
    }
}
