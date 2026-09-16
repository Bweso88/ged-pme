<?php

declare(strict_types=1);

namespace App\Domain\Audit\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Point d'entrée unique pour écrire dans le journal d'audit (voir doc 04, §17 du cahier des
 * charges). Ne jamais écrire directement dans AuditLog ailleurs dans l'application : cela
 * garantit que company_id/ip/horodatage sont toujours renseignés de façon cohérente.
 */
class AuditLogger
{
    public function log(User $user, string $action, ?Model $resource = null, array $details = []): AuditLog
    {
        return AuditLog::query()->create([
            'company_id' => $user->company_id,
            'utilisateur_id' => $user->id,
            'action' => $action,
            'ressource_type' => $resource !== null ? $this->resourceType($resource) : null,
            'ressource_id' => $resource?->getKey(),
            'ip_adresse' => request()?->ip(),
            'details' => $details,
        ]);
    }

    private function resourceType(Model $resource): string
    {
        return match ($resource::class) {
            \App\Models\Document::class => 'document',
            \App\Models\Folder::class => 'folder',
            \App\Models\User::class => 'user',
            \App\Models\Role::class => 'role',
            \App\Models\DocumentShare::class => 'document_share',
            default => class_basename($resource),
        };
    }
}
