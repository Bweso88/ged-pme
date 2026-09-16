<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\AuditLog;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class FolderService
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function create(?Folder $parent, string $nom, User $author): Folder
    {
        $folder = Folder::query()->create([
            'parent_id' => $parent?->id,
            'service_id' => $parent?->service_id,
            'nom' => $nom,
            'created_by' => $author->id,
        ]);

        $this->auditLogger->log($author, AuditLog::CREATION, $folder);

        return $folder;
    }

    public function delete(Folder $folder, User $author): void
    {
        if ($folder->children()->exists() || $folder->documents()->exists()) {
            throw ValidationException::withMessages(['folder' => 'Ce dossier n\'est pas vide.']);
        }

        $this->auditLogger->log($author, AuditLog::SUPPRESSION, $folder);

        $folder->delete();
    }
}
