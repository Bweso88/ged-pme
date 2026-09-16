<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use BelongsToCompany, HasUuids;

    protected $fillable = ['company_id', 'nom', 'code', 'duree_conservation_mois'];

    public function metadataFields(): HasMany
    {
        return $this->hasMany(MetadataField::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function retentionPolicy(): HasMany
    {
        return $this->hasMany(RetentionPolicy::class);
    }
}
