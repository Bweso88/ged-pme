<?php

declare(strict_types=1);

namespace App\Support\Tenancy;

use App\Models\Company;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Résout et porte le tenant (entreprise) courant pour la durée de la requête/commande.
 *
 * C'est la seule source de vérité utilisée par le Global Scope BelongsToCompany et par la
 * synchronisation de la variable de session PostgreSQL "app.current_company_id" (RLS).
 * Le tenant courant ne doit jamais être déduit d'un paramètre de requête/formulaire modifiable
 * côté client — uniquement de l'utilisateur authentifié (voir doc ged-pme/docs/02, §6.2).
 */
class TenantContext
{
    private ?Company $company = null;

    public function set(Company $company): void
    {
        $this->company = $company;
        $this->syncDatabaseSession($company->id);
    }

    public function clear(): void
    {
        $this->company = null;
        $this->syncDatabaseSession(null);
    }

    public function has(): bool
    {
        return $this->company !== null;
    }

    public function company(): Company
    {
        if ($this->company === null) {
            throw new RuntimeException('Aucun tenant (entreprise) n\'est résolu pour ce contexte.');
        }

        return $this->company;
    }

    public function companyId(): string
    {
        return $this->company()->id;
    }

    /**
     * Exécute le callback dans le contexte d'une entreprise donnée, puis restaure le contexte
     * précédent. Utilisé par les seeders, les commandes artisan et les jobs de file d'attente
     * qui n'ont pas de requête HTTP pour résoudre le tenant automatiquement.
     */
    public function runAs(Company $company, callable $callback): mixed
    {
        $previous = $this->company;

        try {
            $this->set($company);

            return $callback();
        } finally {
            if ($previous !== null) {
                $this->set($previous);
            } else {
                $this->clear();
            }
        }
    }

    private function syncDatabaseSession(?string $companyId): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Valeur toujours un UUID généré par l'application (jamais une entrée utilisateur brute) :
        // pas de risque d'injection, mais on revalide le format par prudence avant interpolation
        // (SET n'accepte pas les paramètres liés côté pilote pgsql).
        if ($companyId === null) {
            DB::statement("SET app.current_company_id = ''");

            return;
        }

        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $companyId)) {
            throw new RuntimeException('Identifiant de tenant invalide.');
        }

        DB::statement("SET app.current_company_id = '{$companyId}'");
    }
}
