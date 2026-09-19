<?php

declare(strict_types=1);

namespace App\Domain\Documents\EmailCapture;

final class CapturedAttachment
{
    public function __construct(
        public readonly string $filename,
        public readonly string $content,
    ) {}
}
