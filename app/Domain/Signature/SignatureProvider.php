<?php

declare(strict_types=1);

namespace App\Domain\Signature;

use App\Models\Document;

/**
 * Point d'extension signature électronique (voir doc 04, §13.3). "Approuver" en fin de workflow
 * n'a aucune valeur juridique de signature — ceci est le mécanisme distinct qui en a une, via un
 * prestataire tiers. Contrairement à OcrEngine/AntivirusScanner, il n'existe volontairement PAS
 * d'implémentation "no-op silencieuse" : sans fournisseur configuré, une tentative d'envoi doit
 * échouer explicitement (voir NullSignatureProvider) plutôt que de laisser croire qu'un document
 * a été envoyé en signature alors que rien ne s'est produit.
 */
interface SignatureProvider
{
    /**
     * @param Signataire[] $signataires
     * @return string Identifiant de la demande chez le prestataire (à conserver pour le suivi/webhook)
     *
     * @throws SignatureProviderException
     */
    public function send(Document $document, array $signataires): string;

    /**
     * @return string|null Chemin local temporaire du document signé (avec preuve jointe),
     *                      ou null si la signature n'est pas (encore) terminée.
     */
    public function downloadSignedDocument(string $externalId): ?string;
}
