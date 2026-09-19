<?php

declare(strict_types=1);

namespace App\Domain\Documents\EmailCapture;

/**
 * Point d'extension pour la capture email (voir doc 00, section WebDAV/capture) : abstrait le
 * protocole IMAP derrière une interface simple, sur le même principe que OcrEngine/
 * AntivirusScanner. Implémentation par défaut : WebklexEmailInbox.
 */
interface EmailInbox
{
    /** @return iterable<InboundEmail> */
    public function fetchUnseen(): iterable;
}
