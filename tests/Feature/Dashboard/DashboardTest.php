<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\Index;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_dashboard_renders_without_errors(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $this->actingAsCompanyUser($company, Role::EMPLOYE);

        Livewire::test(Index::class)->assertOk();
    }

    public function test_weekly_document_counts_cover_eight_weeks_including_the_current_one(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        Document::query()->create([
            'folder_id' => $folder->id,
            'nom' => 'note.txt',
            'statut' => Document::STATUT_BROUILLON,
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        $series = Livewire::test(Index::class)->viewData('documentsParSemaine');

        $this->assertCount(8, $series);
        $this->assertSame(1, $series[7]['total']);
        $this->assertSame(0, $series[0]['total']);
    }

    public function test_status_breakdown_lists_every_status_including_those_with_no_document(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        Document::query()->create([
            'folder_id' => $folder->id,
            'nom' => 'facture.pdf',
            'statut' => Document::STATUT_PUBLIE,
            'auteur_id' => $user->id,
            'proprietaire_id' => $user->id,
        ]);

        $breakdown = Livewire::test(Index::class)->viewData('documentsParStatut');

        $this->assertCount(5, $breakdown);
        $this->assertSame(1, collect($breakdown)->firstWhere('label', 'Publié')['total']);
        $this->assertSame(0, collect($breakdown)->firstWhere('label', 'Brouillon')['total']);
    }
}
