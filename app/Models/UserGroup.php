<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserGroup extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = ['company_id', 'nom'];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_group_members', 'group_id', 'user_id');
    }
}
