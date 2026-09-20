<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Role;
use Database\Seeders\SystemRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAvailableForTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_is_never_assignable_to_a_company_user(): void
    {
        $this->seed(SystemRoleSeeder::class);
        $company = Company::query()->create(['nom' => 'Entreprise Test', 'slug' => 'entreprise-test']);

        $codes = Role::availableFor($company)->pluck('code');

        $this->assertNotContains(Role::SUPER_ADMIN, $codes);
        $this->assertContains(Role::ADMIN_ENTREPRISE, $codes);
    }
}
