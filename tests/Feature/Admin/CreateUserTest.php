<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Users\Index;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
