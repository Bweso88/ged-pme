<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchiveRecord extends Model
{
    use BelongsToCompany, HasUuids;

    public const STATUT_ACTIF = 'actif';

    public const STATUT_PROPOSE_DESTRUCTION = 'propose_destruction';

    public const STATUT_VALIDE_DESTRUCTION = 'valide_destruction';

    public const STATUT_DETRUIT = 'detruit';

    protected $fillable = [
        'company_id', 'document_id', 'date_archivage', 'categorie', 'confidentialite', 'motif',
        'date_destruction_prevue', 'statut', 'valide_par', 'valide_at',
    ];

    protected function casts(): array
    {
        return [
            'date_archivage' => 'datetime',
            'date_destruction_prevue' => 'date',
            'valide_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function valideParUtilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }
}
