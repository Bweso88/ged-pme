<?php

declare(strict_types=1);

namespace Tests\Feature\Sharing;

use App\Domain\Sharing\Services\ShareService;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class ShareTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    private function makeDocument(\App\Models\User $user): Document
    {
        $folder = Folder::query()->create(['nom' => 'Achats', 'created_by' => $user->id]);

        return Document::query()->create([
            'folder_id' => $folder->id, 'nom' => 'doc.txt', 'auteur_id' => $user->id, 'proprietaire_id' => $user->id,
        ]);
    }

    public function test_a_share_link_without_expiration_is_rejected(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($user);

        $this->expectException(ValidationException::class);
        app(ShareService::class)->createLink($document, $user, now()->subDay());
    }

    public function test_link_cannot_be_created_directly_in_database_without_expiration(): void
    {
        // Double garde-fou : même en contournant le service, la contrainte SQL l'interdit
        // (voir migration create_document_shares_table, doc 04 §19).
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($user);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DocumentShare::query()->create([
            'document_id' => $document->id, 'cree_par' => $user->id, 'type' => DocumentShare::TYPE_LIEN,
            'token' => 'abc', 'expire_at' => null,
        ]);
    }

    public function test_a_valid_link_can_be_resolved_with_correct_password(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($user);

        $share = app(ShareService::class)->createLink($document, $user, now()->addDay(), 'secret');

        $resolved = app(ShareService::class)->resolveLink($share->token, 'secret');
        $this->assertSame($share->id, $resolved->id);

        $this->expectException(ValidationException::class);
        app(ShareService::class)->resolveLink($share->token, 'wrong-password');
    }

    public function test_a_revoked_link_can_no_longer_be_resolved(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($user);

        $share = app(ShareService::class)->createLink($document, $user, now()->addDay());
        app(ShareService::class)->revoke($share, $user);

        $this->expectException(ValidationException::class);
        app(ShareService::class)->resolveLink($share->token, null);
    }

    public function test_sharing_with_a_colleague_sends_a_notification(): void
    {
        Notification::fake();
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $document = $this->makeDocument($user);
        $colleague = $this->actingAsCompanyUser($company, Role::LECTEUR);

        app(ShareService::class)->shareWithUser($document, $colleague, $user);

        Notification::assertSentTo($colleague, \App\Notifications\DocumentShared::class);
    }
}
