<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Documents\Services\EmailCaptureService;
use Illuminate\Console\Command;

class CaptureEmails extends Command
{
    protected $signature = 'ged:capture-emails';

    protected $description = 'Dépose dans YOSEFA les pièces jointes des emails non lus de la boîte de capture configurée (GED_EMAIL_CAPTURE_*)';

    public function handle(): int
    {
        if (! config('ged.email_capture.enabled')) {
            $this->info('Capture email désactivée (GED_EMAIL_CAPTURE_ENABLED=false).');

            return self::SUCCESS;
        }

        $deposited = app(EmailCaptureService::class)->capture();

        $this->info("{$deposited} pièce(s) jointe(s) déposée(s) dans YOSEFA.");

        return self::SUCCESS;
    }
}
