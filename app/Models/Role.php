<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * company_id NULL = rôle système partagé par toutes les entreprises (super_admin,
 * admin_entreprise, responsable_documentaire, manager, employe, lecteur).
 */
class Role extends Model
{
    use HasUuids;

    public const SUPER_ADMIN = 'super_admin';

    public const ADMIN_ENTREPRISE = 'admin_entreprise';

    public const RESPONSABLE_DOCUMENTAIRE = 'responsable_documentaire';

    public const MANAGER = 'manager';

    public const EMPLOYE = 'employe';

    public const LECTEUR = 'lecteur';

    protected $fillable = ['company_id', 'nom', 'code', 'is_system'];

    protected function casts(): array
    {
        return ['is_system' => 'boolean'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    /** Rôles système + rôles personnalisés de l'entreprise donnée. */
    public static function availableFor(Company $company): Builder
    {
        return static::query()
            ->where(function (Builder $query) use ($company) {
                $query->whereNull('company_id')->orWhere('company_id', $company->id);
            });
    }
}
