<?php

declare(strict_types=1);

namespace App\Domain\Signature\Providers;

use App\Domain\Signature\Signataire;
use App\Domain\Signature\SignatureProvider;
use App\Domain\Signature\SignatureProviderException;
use App\Models\Document;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Intégration Yousign (API v3, https://developers.yousign.com). Implémentation écrite d'après la
 * documentation publique de l'API — NON vérifiée en conditions réelles dans cet environnement
 * (pas de compte/clé API disponible, réseau sortant restreint). À valider avec une clé API
 * sandbox avant toute mise en production, en particulier le format exact des endpoints, qui peut
 * évoluer d'une version d'API à l'autre.
 */
class YousignSignatureProvider implements SignatureProvider
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
    ) {}

    public function send(Document $document, array $signataires): string
    {
        $version = $document->versionCourante;

        if ($version === null) {
            throw new SignatureProviderException('Le document n\'a aucune version courante à signer.');
        }

        $fileContents = Storage::disk(config('ged.storage_disk'))->get($version->storage_path);

        $signatureRequestId = $this->createSignatureRequest($document->nom);
        $documentId = $this->attachDocument($signatureRequestId, $document->nom, $fileContents);

        foreach ($signataires as $signataire) {
            $this->addSigner($signatureRequestId, $documentId, $signataire);
        }

        $this->activate($signatureRequestId);

        return $signatureRequestId;
    }

    public function downloadSignedDocument(string $externalId): ?string
    {
        $status = $this->request()->get("/signature_requests/{$externalId}")->throw()->json();

        if (($status['status'] ?? null) !== 'done') {
            return null;
        }

        $response = $this->request()->get("/signature_requests/{$externalId}/documents/download")->throw();

        $tmpPath = tempnam(sys_get_temp_dir(), 'yousign_signed_').'.pdf';
        file_put_contents($tmpPath, $response->body());

        return $tmpPath;
    }

    private function createSignatureRequest(string $documentName): string
    {
        $response = $this->request()->post('/signature_requests', [
            'name' => "Signature — {$documentName}",
            'delivery_mode' => 'email',
        ])->throw()->json();

        return $response['id'];
    }

    private function attachDocument(string $signatureRequestId, string $filename, string $contents): string
    {
        $response = $this->request()
            ->attach('file', $contents, $filename)
            ->post("/signature_requests/{$signatureRequestId}/documents", ['nature' => 'signable_document'])
            ->throw()->json();

        return $response['id'];
    }

    private function addSigner(string $signatureRequestId, string $documentId, Signataire $signataire): void
    {
        [$prenom, $nom] = array_pad(explode(' ', $signataire->nom, 2), 2, '');

        $this->request()->post("/signature_requests/{$signatureRequestId}/signers", [
            'info' => [
                'first_name' => $prenom,
                'last_name' => $nom ?: $prenom,
                'email' => $signataire->email,
                'locale' => 'fr',
            ],
            'signature_level' => 'electronic_signature',
            'signature_authentication_mode' => 'no_otp',
            'fields' => [
                ['type' => 'signature', 'document_id' => $documentId, 'page' => 1, 'x' => 100, 'y' => 100],
            ],
        ])->throw();
    }

    private function activate(string $signatureRequestId): void
    {
        $this->request()->post("/signature_requests/{$signatureRequestId}/activate")->throw();
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)->withToken($this->apiKey)->acceptJson();
    }
}
