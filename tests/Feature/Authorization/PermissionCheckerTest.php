<?php

declare(strict_types=1);

namespace Tests\Feature\Authorization;

use App\Models\Document;
use App\Models\Folder;
use App\Models\Permission;
use App\Models\ResourcePermission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class PermissionCheckerTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_lecteur_can_view_but_not_download_by_default(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::LECTEUR);

        $this->assertTrue($user->hasPermission('document.view'));
        $this->assertFalse($user->hasPermission('document.download'));
    }

    public function test_explicit_resource_grant_overrides_missing_role_permission(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::LECTEUR);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'doc.txt', 'auteur_id' => $user->id, 'proprietaire_id' => $user->id,
        ]);

        $this->assertFalse($user->hasPermission('document.download', $document));

        ResourcePermission::query()->create([
            'user_id' => $user->id, 'resource_type' => ResourcePermission::TYPE_DOCUMENT,
            'resource_id' => $document->id, 'permission_code' => 'document.download', 'granted' => true,
        ]);

        $this->assertTrue($user->hasPermission('document.download', $document->fresh()));
    }

    public function test_explicit_deny_on_parent_folder_overrides_role_permission(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::MANAGER);
        $folder = Folder::query()->create(['nom' => 'RH confidentiel', 'created_by' => $user->id]);
        $document = Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'salaire.txt', 'auteur_id' => $user->id, 'proprietaire_id' => $user->id,
        ]);

        $this->assertTrue($user->hasPermission('document.view', $document), 'Un manager voit les documents par défaut.');

        ResourcePermission::query()->create([
            'user_id' => $user->id, 'resource_type' => ResourcePermission::TYPE_FOLDER,
            'resource_id' => $folder->id, 'permission_code' => 'document.view', 'granted' => false,
        ]);

        $this->assertFalse($user->hasPermission('document.view', $document->fresh()), 'Le refus explicite sur le dossier parent doit primer.');
    }

    public function test_admin_entreprise_has_every_catalog_permission(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $admin = $this->actingAsCompanyUser($company, Role::ADMIN_ENTREPRISE);

        foreach (Permission::query()->pluck('code') as $code) {
            $this->assertTrue($admin->hasPermission($code), "L'admin entreprise devrait avoir la permission {$code}");
        }
    }
}
