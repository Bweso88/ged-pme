<?php

declare(strict_types=1);

namespace App\Domain\Signature;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Documents\Services\DocumentUploadService;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\SignatureRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class SignatureService
{
    public function __construct(
        private readonly SignatureProvider $provider,
        private readonly DocumentUploadService $uploadService,
        private readonly AuditLogger $auditLogger,
    ) {}

    public function isConfigured(): bool
    {
        return config('ged.signature.driver') === 'yousign';
    }

    /**
     * @param Signataire[] $signataires
     *
     * @throws SignatureProviderException
     */
    public function requestSignature(Document $document, array $signataires, User $demandeur): SignatureRequest
    {
        $record = SignatureRequest::query()->create([
            'document_id' => $document->id,
            'demande_par_id' => $demandeur->id,
            'provider' => (string) config('ged.signature.driver'),
            'statut' => SignatureRequest::STATUT_EN_ATTENTE,
            'signataires' => array_map(fn (Signataire $s) => $s->toArray(), $signataires),
        ]);

        try {
            $externalId = $this->provider->send($document, $signataires);
        } catch (SignatureProviderException $exception) {
            $record->forceFill(['statut' => SignatureRequest::STATUT_ERREUR])->save();

            throw $exception;
        }

        $record->forceFill(['external_id' => $externalId, 'statut' => SignatureRequest::STATUT_ENVOYE])->save();

        $this->auditLogger->log($demandeur, AuditLog::SIGNATURE, $document, [
            'action' => 'demande_signature',
            'destinataires' => array_column($record->signataires, 'email'),
        ]);

        return $record;
    }

    /** Interroge le prestataire pour une demande en cours ; sans effet si elle n'est pas terminée. */
    public function refreshStatus(SignatureRequest $signatureRequest): void
    {
        if ($signatureRequest->external_id === null || $signatureRequest->statut !== SignatureRequest::STATUT_ENVOYE) {
            return;
        }

        $signedPath = $this->provider->downloadSignedDocument($signatureRequest->external_id);

        if ($signedPath === null) {
            return;
        }

        try {
            $uploadedFile = new UploadedFile($signedPath, $signatureRequest->document->nom, test: true);
            $this->uploadService->addVersion(
                $signatureRequest->document,
                $uploadedFile,
                $signatureRequest->demandePar,
                'Document signé électroniquement ('.$signatureRequest->provider.')',
            );
            $signatureRequest->forceFill(['statut' => SignatureRequest::STATUT_SIGNE])->save();

            $this->auditLogger->log(
                $signatureRequest->demandePar,
                AuditLog::SIGNATURE,
                $signatureRequest->document,
                ['action' => 'signature_recue'],
            );
        } finally {
            @unlink($signedPath);
        }
    }
}
