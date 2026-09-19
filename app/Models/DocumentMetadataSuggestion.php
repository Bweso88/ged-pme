<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentMetadataSuggestion extends Model
{
    use BelongsToCompany, HasUuids;

    public const STATUT_EN_ATTENTE = 'en_attente';

    public const STATUT_ACCEPTEE = 'acceptee';

    public const STATUT_REJETEE = 'rejetee';

    protected $fillable = [
        'company_id', 'document_id', 'metadata_field_id', 'valeur_proposee', 'statut',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function metadataField(): BelongsTo
    {
        return $this->belongsTo(MetadataField::class);
    }
}
