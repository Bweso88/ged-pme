<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Users\Index;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_creating_a_user_writes_an_audit_log_entry_without_failing(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);
        $role = Role::query()->whereNull('company_id')->where('code', Role::EMPLOYE)->firstOrFail();

        Livewire::test(Index::class)
            ->set('name', 'Nouvel Utilisateur')
            ->set('email', 'nouvel.utilisateur@test.local')
            ->set('roleId', (string) $role->id)
            ->call('createUser')
            ->assertHasNoErrors();

        $newUser = User::query()->where('email', 'nouvel.utilisateur@test.local')->sole();

        $log = AuditLog::query()->where('ressource_type', 'user')->where('ressource_id', (string) $newUser->id)->sole();
        $this->assertSame(AuditLog::CREATION, $log->action);
    }

    public function test_suspending_a_user_writes_an_audit_log_entry_without_failing(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);
        $target = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);

        Livewire::test(Index::class)
            ->call('toggleSuspend', $target->id)
            ->assertHasNoErrors();

        $this->assertSame('suspendu', $target->fresh()->statut);

        $log = AuditLog::query()
            ->where('ressource_type', 'user')
            ->where('ressource_id', (string) $target->id)
            ->where('action', AuditLog::CHANGEMENT_PERMISSION)
            ->sole();
        $this->assertSame('suspendu', $log->details['nouveau_statut']);
    }

    public function test_resetting_a_password_lets_the_user_log_in_with_the_generated_password(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);
        $target = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);

        $component = Livewire::test(Index::class)
            ->call('resetPassword', $target->id)
            ->assertHasNoErrors();

        $generatedPassword = $component->get('generatedPassword');

        $this->assertNotEmpty($generatedPassword);
        $this->assertTrue(Hash::check($generatedPassword, $target->fresh()->password));

        $log = AuditLog::query()
            ->where('ressource_type', 'user')
            ->where('ressource_id', (string) $target->id)
            ->where('action', AuditLog::MODIFICATION)
            ->sole();
        $this->assertSame('reinitialisation_mot_de_passe', $log->details['action']);
    }

    public function test_an_admin_cannot_reset_the_password_of_a_user_from_another_company(): void
    {
        $this->seedCatalog();
        $companyA = $this->createCompany('Entreprise A');
        $this->actingAsCompanyUser($companyA, Role::ADMIN_ENTREPRISE);

        $companyB = $this->createCompany('Entreprise B');
        $targetInCompanyB = $this->actingAsCompanyUser($companyB, Role::EMPLOYE);

        $this->actingAsCompanyUser($companyA, Role::ADMIN_ENTREPRISE);

        $this->expectException(ModelNotFoundException::class);
        Livewire::test(Index::class)->call('resetPassword', $targetInCompanyB->id);
    }

    public function test_an_admin_cannot_suspend_a_user_from_another_company(): void
    {
        $this->seedCatalog();
        $companyA = $this->createCompany('Entreprise A');
        $this->actingAsCompanyUser($companyA, Role::ADMIN_ENTREPRISE);

        $companyB = $this->createCompany('Entreprise B');
        $targetInCompanyB = $this->actingAsCompanyUser($companyB, Role::EMPLOYE);

        $this->actingAsCompanyUser($companyA, Role::ADMIN_ENTREPRISE);

        $this->expectException(ModelNotFoundException::class);
        Livewire::test(Index::class)->call('toggleSuspend', $targetInCompanyB->id);
    }
}
