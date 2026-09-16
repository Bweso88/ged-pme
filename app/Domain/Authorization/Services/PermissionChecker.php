<?php

declare(strict_types=1);

namespace App\Domain\Authorization\Services;

use App\Models\Document;
use App\Models\Folder;
use App\Models\ResourcePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Résout une permission métier pour un utilisateur, avec la matrice décrite dans
 * ged-pme/docs/04-securite-workflow-archivage.md, §12 :
 *   1. un refus explicite au niveau de la ressource (ou d'un dossier ancêtre) est prioritaire ;
 *   2. sinon, un octroi explicite au niveau de la ressource (ou d'un ancêtre) suffit ;
 *   3. sinon, on retombe sur les permissions accordées par les rôles de l'utilisateur.
 */
class PermissionChecker
{
    public function userCan(User $user, string $permissionCode, ?Model $resource = null): bool
    {
        if ($user->isLocked() || $user->statut !== 'actif') {
            return false;
        }

        if ($resource instanceof Document || $resource instanceof Folder) {
            $override = $this->resolveResourceOverride($user, $permissionCode, $resource);

            if ($override !== null) {
                return $override;
            }
        }

        return $this->hasRolePermission($user, $permissionCode);
    }

    private function hasRolePermission(User $user, string $permissionCode): bool
    {
        return $user->roles()
            ->whereHas('permissions', fn ($query) => $query->where('code', $permissionCode))
            ->exists();
    }

    /**
     * Parcourt la ressource et ses dossiers ancêtres du plus spécifique au plus général.
     * Retourne true/false dès qu'une entrée explicite est trouvée, null si aucune ne s'applique.
     */
    private function resolveResourceOverride(User $user, string $permissionCode, Document|Folder $resource): ?bool
    {
        $groupIds = $user->groups()->pluck('user_groups.id');

        foreach ($this->resourceChain($resource) as [$type, $id]) {
            $query = ResourcePermission::query()
                ->where('resource_type', $type)
                ->where('resource_id', $id)
                ->where('permission_code', $permissionCode)
                ->where(function ($q) use ($user, $groupIds) {
                    $q->where('user_id', $user->id)
                        ->orWhereIn('user_group_id', $groupIds);
                });

            $entries = $query->get();

            if ($entries->isEmpty()) {
                continue;
            }

            if ($entries->contains(fn (ResourcePermission $entry) => $entry->granted === false)) {
                return false;
            }

            return true;
        }

        return null;
    }

    /** @return list<array{0: string, 1: string}> */
    private function resourceChain(Document|Folder $resource): array
    {
        $chain = [];

        if ($resource instanceof Document) {
            $chain[] = [ResourcePermission::TYPE_DOCUMENT, $resource->id];
            $folder = $resource->folder;
        } else {
            $folder = $resource;
        }

        while ($folder !== null) {
            $chain[] = [ResourcePermission::TYPE_FOLDER, $folder->id];
            $folder = $folder->parent;
        }

        return $chain;
    }
}
