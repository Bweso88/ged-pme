<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Table append-only : aucune route applicative n'expose d'UPDATE/DELETE sur ce modèle
 * (voir doc ged-pme/docs/03-modele-donnees.md).
 */
class AuditLog extends Model
{
    use BelongsToCompany, HasUuids;

    public const CONNEXION = 'connexion';

    public const DECONNEXION = 'deconnexion';

    public const CREATION = 'creation';

    public const CONSULTATION = 'consultation';

    public const TELECHARGEMENT = 'telechargement';

    public const MODIFICATION = 'modification';

    public const DEPLACEMENT = 'deplacement';

    public const PARTAGE = 'partage';

    public const SUPPRESSION = 'suppression';

    public const RESTAURATION = 'restauration';

    public const VALIDATION = 'validation';

    public const ARCHIVAGE = 'archivage';

    public const CHANGEMENT_PERMISSION = 'changement_permission';

    public const SIGNATURE = 'signature';

    public $timestamps = false;

    protected $fillable = ['company_id', 'utilisateur_id', 'action', 'ressource_type', 'ressource_id', 'ip_adresse', 'details'];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_at ??= now();
        });
    }

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
