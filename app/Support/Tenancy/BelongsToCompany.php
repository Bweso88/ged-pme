<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Applique automatiquement le filtre "company_id = tenant courant" à toute requête Eloquent
 * sur les modèles qui utilisent ce trait, et renseigne company_id à la création.
 *
 * C'est la première ligne de défense de l'isolation multi-tenant (voir doc ged-pme/docs/02,
 * §6.2) ; la Row-Level Security PostgreSQL (migration enable_row_level_security) est la
 * seconde, indépendante du code applicatif.
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            if (app(TenantContext::class)->has()) {
                $builder->where($builder->getModel()->getTable().'.company_id', app(TenantContext::class)->companyId());
            }
        });

        static::creating(function (Model $model) {
            if ($model->company_id === null && app(TenantContext::class)->has()) {
                $model->company_id = app(TenantContext::class)->companyId();
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Échappe explicitement au scope tenant. Réservé aux traitements Super Admin / commandes
     * planifiées qui doivent légitimement parcourir plusieurs entreprises.
     */
    public static function withoutTenantScope(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }
}
