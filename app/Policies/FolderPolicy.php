<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Folder;
use App\Models\User;

class FolderPolicy
{
    private function sameTenant(User $user, Folder $folder): bool
    {
        return $user->company_id === $folder->company_id;
    }

    public function view(User $user, Folder $folder): bool
    {
        return $this->sameTenant($user, $folder) && $user->hasPermission('document.view', $folder);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('folder.manage');
    }

    public function update(User $user, Folder $folder): bool
    {
        return $this->sameTenant($user, $folder) && $user->hasPermission('folder.manage', $folder);
    }

    public function delete(User $user, Folder $folder): bool
    {
        return $this->sameTenant($user, $folder) && $user->hasPermission('folder.manage', $folder);
    }
}
