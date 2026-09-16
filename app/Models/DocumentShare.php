<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentShare extends Model
{
    use BelongsToCompany, HasUuids;

    public const TYPE_UTILISATEUR = 'utilisateur';

    public const TYPE_GROUPE = 'groupe';

    public const TYPE_LIEN = 'lien';

    protected $fillable = [
        'company_id', 'document_id', 'cree_par', 'type', 'cible_user_id', 'cible_group_id',
        'token', 'mot_de_passe_hash', 'expire_at', 'revoque',
    ];

    protected $hidden = ['mot_de_passe_hash'];

    protected function casts(): array
    {
        return [
            'expire_at' => 'datetime',
            'revoque' => 'boolean',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cree_par');
    }

    public function cibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cible_user_id');
    }

    public function cibleGroup(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'cible_group_id');
    }

    public function isActive(): bool
    {
        if ($this->revoque) {
            return false;
        }

        return $this->expire_at === null || $this->expire_at->isFuture();
    }
}
