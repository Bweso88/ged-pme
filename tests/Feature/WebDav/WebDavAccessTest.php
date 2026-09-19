<?php

declare(strict_types=1);

namespace Tests\Feature\WebDav;

use App\Domain\Documents\Services\DocumentUploadService;
use App\Models\Folder;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class WebDavAccessTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_propfind_without_credentials_is_rejected(): void
    {
        $response = $this->call('PROPFIND', '/webdav/', server: ['HTTP_DEPTH' => '0']);

        $response->assertStatus(401);
    }

    public function test_propfind_with_wrong_password_is_rejected(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $this->actingAsCompanyUser($company, Role::EMPLOYE, ['email' => 'reseau@test.local', 'password' => 'bon-mot-de-passe']);

        $response = $this->call('PROPFIND', '/webdav/', server: [
            ...$this->basicAuth('reseau@test.local', 'mauvais-mot-de-passe'),
            'HTTP_DEPTH' => '0',
        ]);

        $response->assertStatus(401);
    }

    public function test_propfind_lists_folders_the_user_can_view(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE, ['email' => 'reseau@test.local', 'password' => 'bon-mot-de-passe']);
        Folder::query()->create(['nom' => 'Contrats', 'created_by' => $user->id]);

        $response = $this->call('PROPFIND', '/webdav/', server: [
            ...$this->basicAuth('reseau@test.local', 'bon-mot-de-passe'),
            'HTTP_DEPTH' => '1',
        ]);

        $response->assertStatus(207);
        $this->assertStringContainsString('Contrats', $response->getContent());
    }

    public function test_get_downloads_the_current_version_content(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE, ['email' => 'reseau@test.local', 'password' => 'bon-mot-de-passe']);
        $folder = Folder::query()->create(['nom' => 'Contrats', 'created_by' => $user->id]);
        app(DocumentUploadService::class)->upload($folder, UploadedFile::fake()->createWithContent('bail.txt', 'contenu du bail'), $user);

        $response = $this->call('GET', '/webdav/Contrats/bail.txt', server: $this->basicAuth('reseau@test.local', 'bon-mot-de-passe'));

        $response->assertStatus(200);
        $this->assertSame('contenu du bail', $response->streamedContent());
    }

    public function test_put_creates_a_new_version_when_the_user_has_the_right(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE, ['email' => 'reseau@test.local', 'password' => 'bon-mot-de-passe']);
        $folder = Folder::query()->create(['nom' => 'Contrats', 'created_by' => $user->id]);
        $document = app(DocumentUploadService::class)->upload($folder, UploadedFile::fake()->createWithContent('bail.txt', 'v1'), $user);

        $response = $this->call('PUT', '/webdav/Contrats/bail.txt', server: $this->basicAuth('reseau@test.local', 'bon-mot-de-passe'), content: 'v2');

        $response->assertStatus(204);
        $this->assertSame(2, $document->fresh()->versions()->count());
    }

    public function test_a_folder_from_another_company_is_never_listed(): void
    {
        $this->seedCatalog();
        $companyA = $this->createCompany('Entreprise A');
        $userA = $this->actingAsCompanyUser($companyA, Role::EMPLOYE, ['email' => 'a@test.local', 'password' => 'mot-de-passe-a']);
        Folder::query()->create(['nom' => 'DossierA', 'created_by' => $userA->id]);

        $companyB = $this->createCompany('Entreprise B');
        $userB = $this->actingAsCompanyUser($companyB, Role::EMPLOYE, ['email' => 'b@test.local', 'password' => 'mot-de-passe-b']);
        Folder::query()->create(['nom' => 'DossierB', 'created_by' => $userB->id]);

        $response = $this->call('PROPFIND', '/webdav/', server: [
            ...$this->basicAuth('a@test.local', 'mot-de-passe-a'),
            'HTTP_DEPTH' => '1',
        ]);

        $response->assertStatus(207);
        $this->assertStringContainsString('DossierA', $response->getContent());
        $this->assertStringNotContainsString('DossierB', $response->getContent());
    }

    /** @return array{HTTP_AUTHORIZATION: string} */
    private function basicAuth(string $email, string $password): array
    {
        return ['HTTP_AUTHORIZATION' => 'Basic '.base64_encode("{$email}:{$password}")];
    }
}
