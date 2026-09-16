<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Authorization\Services\PermissionChecker;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'company_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'derniere_connexion_at' => 'datetime',
            'verrouille_jusqu_a' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'user_group_members', 'user_id', 'group_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->company_id === null;
    }

    public function isLocked(): bool
    {
        return $this->verrouille_jusqu_a !== null && $this->verrouille_jusqu_a->isFuture();
    }

    /**
     * Vérifie une permission métier (ex. "document.archive"), avec surcharge éventuelle au
     * niveau d'une ressource précise (dossier/document). Utilisé par les Policies — voir
     * App\Domain\Authorization\Services\PermissionChecker.
     */
    public function hasPermission(string $permissionCode, ?Model $resource = null): bool
    {
        return app(PermissionChecker::class)->userCan($this, $permissionCode, $resource);
    }
}
