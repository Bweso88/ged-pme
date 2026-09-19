<?php

declare(strict_types=1);

namespace App\Domain\Signature;

final class Signataire
{
    public function __construct(
        public readonly string $nom,
        public readonly string $email,
    ) {}

    /** @return array{nom: string, email: string} */
    public function toArray(): array
    {
        return ['nom' => $this->nom, 'email' => $this->email];
    }
}
