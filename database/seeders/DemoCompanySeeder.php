<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\DocumentType;
use App\Models\Folder;
use App\Models\RetentionPolicy;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Entreprise de démonstration + structure documentaire par défaut (voir doc 01, §5),
 * utile pour explorer l'application en local sans devoir tout configurer à la main.
 */
class DemoCompanySeeder extends Seeder
{
    public function run(): void
    {
        $tenant = app(TenantContext::class);

        $company = Company::query()->firstOrCreate(
            ['slug' => 'demo'],
            ['nom' => 'Entreprise Démo', 'plan' => 'starter', 'statut' => 'actif'],
        );

        $tenant->runAs($company, function () use ($company) {
            $admin = User::query()->firstOrCreate(
                ['email' => 'admin@demo.test'],
                [
                    'company_id' => $company->id,
                    'name' => 'Administratrice Démo',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );

            $adminRole = Role::query()->where('company_id', null)->where('code', Role::ADMIN_ENTREPRISE)->first();
            if ($adminRole !== null && ! $admin->roles()->where('roles.id', $adminRole->id)->exists()) {
                $admin->roles()->attach($adminRole);
            }

            $racine = Folder::query()->firstOrCreate(
                ['company_id' => $company->id, 'parent_id' => null, 'nom' => 'Entreprise'],
                ['created_by' => $admin->id],
            );

            foreach (['Direction', 'Ressources humaines', 'Finance', 'Juridique', 'Commercial', 'Informatique', 'Achats', 'Projets'] as $nom) {
                Folder::query()->firstOrCreate(
                    ['company_id' => $company->id, 'parent_id' => $racine->id, 'nom' => $nom],
                    ['created_by' => $admin->id],
                );
            }

            $contrat = DocumentType::query()->firstOrCreate(
                ['company_id' => $company->id, 'code' => 'contrat'],
                ['nom' => 'Contrat', 'duree_conservation_mois' => 120],
            );

            DocumentType::query()->firstOrCreate(
                ['company_id' => $company->id, 'code' => 'facture'],
                ['nom' => 'Facture', 'duree_conservation_mois' => 72],
            );

            RetentionPolicy::query()->firstOrCreate(
                ['company_id' => $company->id, 'document_type_id' => $contrat->id],
                [
                    'duree_conservation_mois' => 120,
                    'action_a_expiration' => 'demander_validation',
                    'categorie_archive' => 'contrats',
                ],
            );
        });
    }
}
