<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Catalogue global de permissions atomiques (document.view, document.archive, ...).
 * N'appartient à aucun tenant : partagé par toutes les entreprises.
 */
class Permission extends Model
{
    use HasUuids;

    protected $fillable = ['code', 'label'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions');
    }
}
