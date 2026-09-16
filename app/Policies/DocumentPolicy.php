<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    private function sameTenant(User $user, Document $document): bool
    {
        // Filet de sécurité en plus du Global Scope + RLS : jamais d'action inter-tenant.
        return $user->company_id === $document->company_id;
    }

    public function view(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.view', $document);
    }

    public function download(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.download', $document);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('document.create');
    }

    public function update(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.edit_metadata', $document);
    }

    public function createVersion(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.new_version', $document);
    }

    public function restoreVersion(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.restore_version', $document);
    }

    public function move(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.move', $document);
    }

    public function share(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.share', $document);
    }

    public function submitWorkflow(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.submit_workflow', $document);
    }

    public function validateWorkflow(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.validate', $document);
    }

    public function archive(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.archive', $document);
    }

    public function trash(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.delete', $document);
    }

    public function restoreFromTrash(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.restore_trash', $document);
    }

    public function forceDelete(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('document.delete_permanent', $document);
    }

    public function viewAuditLog(User $user, Document $document): bool
    {
        return $this->sameTenant($user, $document) && $user->hasPermission('admin.audit', $document);
    }
}
