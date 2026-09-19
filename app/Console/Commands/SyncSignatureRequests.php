<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Signature\SignatureService;
use App\Models\SignatureRequest;
use App\Support\Tenancy\TenantContext;
use Illuminate\Console\Command;

/**
 * Interroge le prestataire de signature pour les demandes en cours (statut "envoye") et
 * rattache le document signé dès qu'il est disponible. Simple polling plutôt qu'un webhook
 * public : plus simple à activer sans exposer d'endpoint non authentifié, suffisant pour le
 * volume attendu (quelques demandes de signature par jour dans une PME).
 */
class SyncSignatureRequests extends Command
{
    protected $signature = 'ged:sync-signatures';

    protected $description = 'Met à jour le statut des demandes de signature électronique en cours (GED_SIGNATURE_DRIVER)';

    public function handle(SignatureService $service, TenantContext $tenantContext): int
    {
        if (! $service->isConfigured()) {
            $this->info('Signature électronique désactivée (GED_SIGNATURE_DRIVER=null).');

            return self::SUCCESS;
        }

        $pending = SignatureRequest::withoutTenantScope()
            ->where('statut', SignatureRequest::STATUT_ENVOYE)
            ->with('document.company')
            ->get();

        foreach ($pending as $signatureRequest) {
            $tenantContext->set($signatureRequest->document->company);
            $service->refreshStatus($signatureRequest);
        }

        $this->info(count($pending).' demande(s) de signature vérifiée(s).');

        return self::SUCCESS;
    }
}
