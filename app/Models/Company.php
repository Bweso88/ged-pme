<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasUuids;

    protected $fillable = [
        'nom', 'slug', 'logo_path', 'couleur_primaire', 'plan', 'statut', 'quota_stockage_mo', 'parametres',
    ];

    protected function casts(): array
    {
        return [
            'parametres' => 'array',
            'quota_stockage_mo' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function folders(): HasMany
    {
        return $this->hasMany(Folder::class);
    }
}
