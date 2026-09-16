<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = ['company_id', 'workflow_definition_id', 'ordre', 'nom', 'role_requis_id', 'user_requis_id'];

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function roleRequis(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_requis_id');
    }

    public function userRequis(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_requis_id');
    }

    /** Un utilisateur peut agir sur cette étape s'il est la personne nommée, ou titulaire du rôle requis. */
    public function canBeActedOnBy(User $user): bool
    {
        if ($this->user_requis_id !== null) {
            return $this->user_requis_id === $user->id;
        }

        if ($this->role_requis_id !== null) {
            return $user->roles()->where('roles.id', $this->role_requis_id)->exists();
        }

        return false;
    }
}
