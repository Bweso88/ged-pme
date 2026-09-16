<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Un document supprimé est mis à la corbeille (is_trashed=true), jamais détruit
 * immédiatement — voir doc 04, §14.4. La suppression définitive est une action distincte,
 * réservée aux titulaires de la permission document.delete_permanent.
 */
class TrashService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function moveToTrash(Document $document, User $user): void
    {
        $document->forceFill([
            'is_trashed' => true,
            'trashed_at' => now(),
            'trashed_by' => $user->id,
        ])->save();

        $this->auditLogger->log($user, AuditLog::SUPPRESSION, $document);
    }

    public function restore(Document $document, User $user): void
    {
        $document->forceFill([
            'is_trashed' => false,
            'trashed_at' => null,
            'trashed_by' => null,
        ])->save();

        $this->auditLogger->log($user, AuditLog::RESTAURATION, $document);
    }

    /** Suppression définitive : purge physique de toutes les versions + entrée d'audit terminale. */
    public function forceDelete(Document $document, User $user): void
    {
        $disk = Storage::disk(config('ged.storage_disk'));

        foreach ($document->versions as $version) {
            $disk->delete($version->storage_path);
        }

        $this->auditLogger->log($user, AuditLog::SUPPRESSION, $document, ['action' => 'suppression_definitive']);

        $document->delete();
    }
}
