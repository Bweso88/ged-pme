<?php

declare(strict_types=1);

namespace Tests\Feature\OnlyOffice;

use App\Domain\Documents\Services\DocumentUploadService;
use App\Domain\OnlyOffice\OnlyOfficeConfigService;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Role;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class OnlyOfficeTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        Config::set('ged.onlyoffice.enabled', true);
    }

    public function test_config_service_builds_a_config_without_token_when_no_secret_is_set(): void
    {
        Config::set('ged.onlyoffice.jwt_secret', null);
        [$document, $user] = $this->makeEditableDocument();

        $config = app(OnlyOfficeConfigService::class)->buildEditorConfig($document, $user);

        $this->assertSame('txt', $config['document']['fileType']);
        $this->assertSame('word', $config['documentType']);
        $this->assertArrayNotHasKey('token', $config);
    }

    public function test_config_service_signs_the_config_when_a_secret_is_set(): void
    {
        Config::set('ged.onlyoffice.jwt_secret', 'a-test-secret-that-is-long-enough-for-hs256-1234');
        [$document, $user] = $this->makeEditableDocument();

        $config = app(OnlyOfficeConfigService::class)->buildEditorConfig($document, $user);

        $this->assertArrayHasKey('token', $config);
        $decoded = JWT::decode($config['token'], new Key('a-test-secret-that-is-long-enough-for-hs256-1234', 'HS256'));
        $this->assertSame('txt', $decoded->document->fileType);
    }

    public function test_content_endpoint_serves_the_current_version_with_a_valid_signature(): void
    {
        [$document] = $this->makeEditableDocument();

        $url = URL::temporarySignedRoute('onlyoffice.content', now()->addMinutes(5), [
            'company' => $document->company_id,
            'document' => $document->id,
        ]);

        $response = $this->get($url);

        $response->assertStatus(200);
    }

    public function test_content_endpoint_rejects_a_tampered_url(): void
    {
        [$document] = $this->makeEditableDocument();

        $url = URL::temporarySignedRoute('onlyoffice.content', now()->addMinutes(5), [
            'company' => $document->company_id,
            'document' => $document->id,
        ]);

        $response = $this->get($url.'&tampered=1');

        $response->assertStatus(403);
    }

    public function test_callback_saves_the_edited_document_as_a_new_version_on_must_save_status(): void
    {
        [$document] = $this->makeEditableDocument();
        Http::fake(['*' => Http::response('contenu edite')]);

        $url = URL::temporarySignedRoute('onlyoffice.callback', now()->addMinutes(5), [
            'company' => $document->company_id,
            'document' => $document->id,
        ]);

        $response = $this->postJson($url, ['status' => 2, 'url' => 'https://document-server.local/edited.docx']);

        $response->assertStatus(200)->assertJson(['error' => 0]);
        $this->assertSame(2, $document->fresh()->versions()->count());
    }

    public function test_callback_does_nothing_for_an_editing_in_progress_status(): void
    {
        [$document] = $this->makeEditableDocument();

        $url = URL::temporarySignedRoute('onlyoffice.callback', now()->addMinutes(5), [
            'company' => $document->company_id,
            'document' => $document->id,
        ]);

        $response = $this->postJson($url, ['status' => 1]);

        $response->assertStatus(200)->assertJson(['error' => 0]);
        $this->assertSame(1, $document->fresh()->versions()->count());
    }

    public function test_callback_rejects_a_request_without_a_valid_jwt_when_a_secret_is_configured(): void
    {
        Config::set('ged.onlyoffice.jwt_secret', 'a-test-secret-that-is-long-enough-for-hs256-1234');
        [$document] = $this->makeEditableDocument();

        $url = URL::temporarySignedRoute('onlyoffice.callback', now()->addMinutes(5), [
            'company' => $document->company_id,
            'document' => $document->id,
        ]);

        $response = $this->postJson($url, ['status' => 2, 'url' => 'https://document-server.local/edited.docx']);

        $response->assertStatus(403);
    }

    public function test_edit_online_route_is_not_available_when_onlyoffice_is_disabled(): void
    {
        Config::set('ged.onlyoffice.enabled', false);
        [$document, $user] = $this->makeEditableDocument();
        $this->actingAs($user);

        $response = $this->get(route('documents.edit-online', $document));

        $response->assertStatus(404);
    }

    /** @return array{0: Document, 1: User} */
    private function makeEditableDocument(): array
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'Contrats', 'created_by' => $user->id]);

        $file = UploadedFile::fake()->createWithContent('contrat.txt', 'contenu original');
        $document = app(DocumentUploadService::class)->upload($folder, $file, $user);

        return [$document, $user];
    }
}
