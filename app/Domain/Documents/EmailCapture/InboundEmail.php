<?php

declare(strict_types=1);

namespace App\Domain\Documents\EmailCapture;

final class InboundEmail
{
    /** @param CapturedAttachment[] $attachments */
    public function __construct(
        public readonly string $subject,
        public readonly array $attachments,
        private readonly \Closure $onProcessed,
    ) {}

    /** Marque le message comme lu (Seen) sur le serveur IMAP — appelé une fois ses pièces jointes traitées. */
    public function markAsProcessed(): void
    {
        ($this->onProcessed)();
    }
}
