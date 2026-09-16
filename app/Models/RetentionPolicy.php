<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RetentionPolicy extends Model
{
    use BelongsToCompany, HasUuids;

    public const ACTION_CONSERVER = 'conserver';

    public const ACTION_PROPOSER_DESTRUCTION = 'proposer_destruction';

    public const ACTION_TRANSFERER_ARCHIVE = 'transferer_archive';

    public const ACTION_DEMANDER_VALIDATION = 'demander_validation';

    protected $fillable = [
        'company_id', 'document_type_id', 'duree_conservation_mois', 'action_a_expiration', 'categorie_archive', 'actif',
    ];

    protected function casts(): array
    {
        return [
            'duree_conservation_mois' => 'integer',
            'actif' => 'boolean',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
