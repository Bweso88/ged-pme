<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Alice',
            'email' => 'alice@test.local',
            'password' => Hash::make('secret-password'),
        ]);

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secret-password')
            ->call('submit')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_account_locks_after_five_failed_attempts(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Bob',
            'email' => 'bob@test.local',
            'password' => Hash::make('secret-password'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', $user->email)
                ->set('password', 'wrong-password')
                ->call('submit')
                ->assertHasErrors('email');
        }

        $this->assertTrue($user->fresh()->isLocked());

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secret-password')
            ->call('submit')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_suspended_account_cannot_log_in(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => 'Carla',
            'email' => 'carla@test.local',
            'password' => Hash::make('secret-password'),
        ]);
        $user->forceFill(['statut' => 'suspendu'])->save();

        Livewire::test(Login::class)
            ->set('email', $user->email)
            ->set('password', 'secret-password')
            ->call('submit')
            ->assertHasErrors('email');

        $this->assertGuest();
    }
}
