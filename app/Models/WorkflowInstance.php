<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowInstance extends Model
{
    use BelongsToCompany, HasUuids;

    public const STATUT_EN_COURS = 'en_cours';

    public const STATUT_TERMINE = 'termine';

    public const STATUT_REJETE = 'rejete';

    public const STATUT_ANNULE = 'annule';

    protected $fillable = ['company_id', 'document_id', 'workflow_definition_id', 'etape_courante_id', 'statut'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function workflowDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    public function etapeCourante(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'etape_courante_id');
    }

    public function actions(): HasMany
    {
        return $this->hasMany(WorkflowAction::class)->orderBy('created_at');
    }
}
