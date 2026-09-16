<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowAction extends Model
{
    use BelongsToCompany, HasUuids;

    public const APPROUVE = 'approuve';

    public const REJETE = 'rejete';

    public const DEMANDE_MODIFICATION = 'demande_modification';

    public const COMMENTAIRE = 'commentaire';

    public $timestamps = false;

    protected $fillable = ['company_id', 'workflow_instance_id', 'workflow_step_id', 'utilisateur_id', 'action', 'commentaire'];

    protected $attributes = [];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->created_at ??= now();
        });
    }

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function workflowInstance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class);
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function utilisateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'utilisateur_id');
    }
}
