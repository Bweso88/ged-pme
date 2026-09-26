<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class FolderTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_it_lists_root_folders_for_the_authenticated_users_company(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        Sanctum::actingAs($user);

        Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        Folder::query()->create(['nom' => 'RH', 'created_by' => $user->id]);

        $response = $this->getJson('/api/v1/folders')->assertOk();

        $this->assertSame(['Achats', 'RH'], collect($response->json('data'))->pluck('nom')->all());
    }

    public function test_a_user_cannot_view_a_folder_belonging_to_another_company(): void
    {
        $this->seedCatalog();

        $companyA = $this->createCompany('A');
        $userA = $this->actingAsCompanyUser($companyA, Role::EMPLOYE);
        $folderA = Folder::query()->create(['nom' => 'Confidentiel', 'created_by' => $userA->id]);

        $companyB = $this->createCompany('B');
        $userB = $this->actingAsCompanyUser($companyB, Role::EMPLOYE);
        Sanctum::actingAs($userB);

        $this->getJson("/api/v1/folders/{$folderA->id}")->assertNotFound();
    }
}
