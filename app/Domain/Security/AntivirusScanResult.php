<?php

declare(strict_types=1);

namespace App\Domain\Security;

final class AntivirusScanResult
{
    private function __construct(
        public readonly bool $clean,
        public readonly ?string $menace = null,
    ) {}

    public static function clean(): self
    {
        return new self(true);
    }

    public static function infected(string $menace): self
    {
        return new self(false, $menace);
    }
}
