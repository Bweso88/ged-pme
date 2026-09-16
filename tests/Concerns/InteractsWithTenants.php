<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Support\Str;

/**
 * Prépare une entreprise et des utilisateurs de test, sans dépendre du seeder de démo.
 * Réinitialise le contexte tenant après chaque test : le GUC PostgreSQL "app.current_company_id"
 * n'est pas transactionnel (SET, pas SET LOCAL) et survivrait donc au rollback de RefreshDatabase.
 */
trait InteractsWithTenants
{
    protected function seedCatalog(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(SystemRoleSeeder::class);
    }

    protected function createCompany(string $nom = 'Entreprise Test'): Company
    {
        return Company::query()->create(['nom' => $nom, 'slug' => Str::slug($nom.'-'.Str::random(6))]);
    }

    protected function actingAsCompanyUser(Company $company, string $roleCode = Role::EMPLOYE, array $attributes = []): User
    {
        $user = User::query()->create(array_merge([
            'company_id' => $company->id,
            'name' => 'Utilisateur '.$roleCode,
            'email' => Str::random(10).'@test.local',
            'password' => 'password',
        ], $attributes));

        $role = Role::query()->whereNull('company_id')->where('code', $roleCode)->firstOrFail();
        $user->roles()->attach($role);

        // "statut" n'est pas mass-assignable (voir #[Fillable] sur App\Models\User) : recharger
        // depuis la base pour obtenir sa valeur par défaut, comme le ferait une vraie requête HTTP.
        $user = $user->fresh();

        app(TenantContext::class)->set($company);
        $this->actingAs($user);

        return $user;
    }

    protected function grantPermission(User $user, string $permissionCode): void
    {
        $permission = Permission::query()->where('code', $permissionCode)->firstOrFail();
        $role = Role::query()->create(['company_id' => $user->company_id, 'nom' => 'Rôle ad hoc '.Str::random(4)]);
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);
    }
}
