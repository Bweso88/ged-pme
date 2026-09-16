<?php

declare(strict_types=1);

namespace Tests\Feature\Tenancy;

use App\Models\Folder;
use App\Models\Role;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

/**
 * Vérifie l'isolation multi-tenant à deux niveaux indépendants (voir doc 02, §6.2) :
 * le Global Scope Eloquent ET, séparément, la Row-Level Security PostgreSQL — en
 * interrogeant directement la table via DB::table() pour prouver que la RLS protège même
 * en cas d'oubli total du Global Scope côté application.
 */
class TenantIsolationTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_row_level_security_hides_rows_of_other_tenants_even_via_raw_query(): void
    {
        $companyA = $this->createCompany('Entreprise A');
        $companyB = $this->createCompany('Entreprise B');

        app(TenantContext::class)->set($companyA);
        DB::table('services')->insert([
            'id' => (string) Str::uuid(), 'company_id' => $companyA->id, 'nom' => 'Service A',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        app(TenantContext::class)->set($companyB);
        $this->assertSame(0, DB::table('services')->count(), 'Le tenant B ne doit voir aucune ligne du tenant A.');

        app(TenantContext::class)->set($companyA);
        $this->assertSame(1, DB::table('services')->count(), 'Le tenant A doit voir sa propre ligne.');
    }

    public function test_user_cannot_access_another_companys_document_via_http(): void
    {
        $this->seedCatalog();

        $companyA = $this->createCompany('Entreprise A');
        $companyB = $this->createCompany('Entreprise B');

        $userA = $this->actingAsCompanyUser($companyA, Role::EMPLOYE);
        $folderA = Folder::query()->create(['nom' => 'Dossier A', 'created_by' => $userA->id]);
        $documentA = \App\Models\Document::query()->create([
            'folder_id' => $folderA->id, 'nom' => 'doc-a.txt', 'auteur_id' => $userA->id, 'proprietaire_id' => $userA->id,
        ]);

        $userB = $this->actingAsCompanyUser($companyB, Role::EMPLOYE);

        $this->get(route('documents.show', $documentA))->assertNotFound();
    }

    public function test_eloquent_global_scope_also_filters_by_tenant(): void
    {
        $companyA = $this->createCompany('Entreprise A');
        $companyB = $this->createCompany('Entreprise B');

        app(TenantContext::class)->set($companyA);
        Folder::query()->create(['nom' => 'Dossier A']);

        app(TenantContext::class)->set($companyB);
        $this->assertSame(0, Folder::query()->count());

        app(TenantContext::class)->set($companyA);
        $this->assertSame(1, Folder::query()->count());
    }
}
