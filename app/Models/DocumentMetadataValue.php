<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Clé primaire composite (document_id, metadata_field_id) : ce modèle est manipulé via le
 * QueryBuilder (where + upsert) dans MetadataValueService plutôt que via find()/save().
 */
class DocumentMetadataValue extends Model
{
    use BelongsToCompany;

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = ['company_id', 'document_id', 'metadata_field_id', 'valeur'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function metadataField(): BelongsTo
    {
        return $this->belongsTo(MetadataField::class);
    }
}
