<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetadataField extends Model
{
    use BelongsToCompany, HasUuids;

    public const TYPE_TEXTE = 'texte';

    public const TYPE_NOMBRE = 'nombre';

    public const TYPE_DATE = 'date';

    public const TYPE_LISTE = 'liste';

    public const TYPE_BOOLEEN = 'booleen';

    protected $fillable = [
        'company_id', 'document_type_id', 'code', 'label', 'type', 'options', 'obligatoire', 'ordre',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'obligatoire' => 'boolean',
        ];
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
