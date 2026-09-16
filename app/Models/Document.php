<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    use BelongsToCompany, HasUuids;

    public const STATUT_BROUILLON = 'brouillon';

    public const STATUT_SOUMIS = 'soumis';

    public const STATUT_EN_VALIDATION = 'en_validation';

    public const STATUT_PUBLIE = 'publie';

    public const STATUT_ARCHIVE = 'archive';

    public const CONFIDENTIALITE_PUBLIC = 'public_entreprise';

    public const CONFIDENTIALITE_RESTREINT = 'restreint';

    public const CONFIDENTIALITE_CONFIDENTIEL = 'confidentiel';

    protected $fillable = [
        'company_id', 'folder_id', 'document_type_id', 'nom', 'reference', 'statut', 'confidentialite',
        'auteur_id', 'proprietaire_id', 'mots_cles', 'date_document', 'date_expiration',
        'version_courante_id', 'is_trashed', 'trashed_at', 'trashed_by',
    ];

    protected function casts(): array
    {
        return [
            'mots_cles' => 'array',
            'date_document' => 'date',
            'date_expiration' => 'date',
            'is_trashed' => 'boolean',
            'trashed_at' => 'datetime',
        ];
    }

    /** Exclut par défaut les documents mis à la corbeille (voir scopeWithTrashed / scopeOnlyTrashed). */
    protected static function booted(): void
    {
        static::addGlobalScope('notTrashed', function (Builder $builder) {
            $builder->where('is_trashed', false);
        });
    }

    public function scopeWithTrashed(Builder $query): Builder
    {
        return $query->withoutGlobalScope('notTrashed');
    }

    public function scopeOnlyTrashed(Builder $query): Builder
    {
        return $query->withoutGlobalScope('notTrashed')->where('is_trashed', true);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proprietaire_id');
    }

    public function trashedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trashed_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('numero_version');
    }

    public function versionCourante(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'version_courante_id');
    }

    public function metadataValues(): HasMany
    {
        return $this->hasMany(DocumentMetadataValue::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class);
    }

    public function workflowInstances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class);
    }

    public function archiveRecords(): HasMany
    {
        return $this->hasMany(ArchiveRecord::class);
    }

    public function currentWorkflowInstance(): ?WorkflowInstance
    {
        return $this->workflowInstances()->where('statut', 'en_cours')->latest()->first();
    }
}
