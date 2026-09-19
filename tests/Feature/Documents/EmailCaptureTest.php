<?php

declare(strict_types=1);

namespace Tests\Feature\Documents;

use App\Domain\Documents\EmailCapture\CapturedAttachment;
use App\Domain\Documents\EmailCapture\EmailInbox;
use App\Domain\Documents\EmailCapture\InboundEmail;
use App\Domain\Documents\Services\EmailCaptureService;
use App\Models\Folder;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class EmailCaptureTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_it_deposits_attachments_from_unseen_emails_and_marks_them_processed(): void
    {
        [$folder, $uploader] = $this->configureCapture();
        $spy = new class
        {
            public bool $processed = false;
        };

        $this->app->bind(EmailInbox::class, fn () => new class($spy) implements EmailInbox
        {
            public function __construct(private object $spy) {}

            public function fetchUnseen(): iterable
            {
                yield new InboundEmail(
                    'Facture fournisseur',
                    [new CapturedAttachment('facture.txt', 'contenu de la facture')],
                    function () {
                        $this->spy->processed = true;
                    },
                );
            }
        });

        $deposited = app(EmailCaptureService::class)->capture();

        $this->assertSame(1, $deposited);
        $this->assertSame(1, $folder->documents()->count());
        $this->assertTrue($spy->processed);
    }

    public function test_a_rejected_attachment_does_not_stop_the_rest_of_the_batch(): void
    {
        [$folder] = $this->configureCapture();

        $this->app->bind(EmailInbox::class, fn () => new class implements EmailInbox
        {
            public function fetchUnseen(): iterable
            {
                yield new InboundEmail('Suspect', [
                    new CapturedAttachment('virus.exe', 'contenu'), // extension interdite
                    new CapturedAttachment('bon.txt', 'contenu valide'),
                ], fn () => null);
            }
        });

        $deposited = app(EmailCaptureService::class)->capture();

        $this->assertSame(1, $deposited);
        $this->assertSame(1, $folder->documents()->count());
        $this->assertSame('bon.txt', $folder->documents()->first()->nom);
    }

    public function test_it_fails_fast_when_target_folder_is_not_configured(): void
    {
        Config::set('ged.email_capture.target_folder_id', null);
        Config::set('ged.email_capture.uploader_user_id', null);

        $this->expectException(RuntimeException::class);
        app(EmailCaptureService::class)->capture();
    }

    /** @return array{0: Folder, 1: User} */
    private function configureCapture(): array
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $folder = Folder::query()->create(['nom' => 'À classer', 'created_by' => $user->id]);

        Config::set('ged.email_capture.target_folder_id', $folder->id);
        Config::set('ged.email_capture.uploader_user_id', $user->id);

        return [$folder, $user];
    }
}
