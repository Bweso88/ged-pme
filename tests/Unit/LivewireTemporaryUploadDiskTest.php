<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Régression : quand FILESYSTEM_DISK pointe vers S3/R2 (Laravel Cloud), Livewire y stocke
 * aussi ses fichiers TEMPORAIRES par défaut — sauf que son driver S3 refuse la sélection de
 * plusieurs fichiers ("S3 temporary file upload driver only supports single file uploads"),
 * ce que notre explorateur de documents permet. Le disque temporaire de Livewire doit donc
 * rester "local" indépendamment de FILESYSTEM_DISK (voir config/livewire.php).
 */
class LivewireTemporaryUploadDiskTest extends TestCase
{
    public function test_livewire_temporary_upload_disk_defaults_to_local(): void
    {
        $this->assertSame('local', config('livewire.temporary_file_upload.disk'));
    }
}
