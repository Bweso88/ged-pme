<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureRequest extends Model
{
    use BelongsToCompany, HasUuids;

    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_ENVOYE = 'envoye';

    public const STATUT_SIGNE = 'signe';

    public const STATUT_REFUSE = 'refuse';

    public const STATUT_EXPIRE = 'expire';

    public const STATUT_ERREUR = 'erreur';

    protected $fillable = ['company_id', 'document_id', 'demande_par_id', 'provider', 'external_id', 'statut', 'signataires'];

    protected function casts(): array
    {
        return ['signataires' => 'array'];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function demandePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demande_par_id');
    }
}
