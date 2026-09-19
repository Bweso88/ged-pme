<?php

declare(strict_types=1);

namespace App\Http\Controllers\OnlyOffice;

use App\Domain\Documents\Services\DocumentUploadService;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Document;
use App\Support\Tenancy\TenantContext;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Reçoit les rappels du Document Server OnlyOffice (voir
 * https://api.onlyoffice.com/docs/docs-api/usage-api/rappel-webhook/). Statuts 2 (MustSave) et
 * 6 (MustForceSave) signalent un document prêt à être récupéré et enregistré comme nouvelle
 * version — les autres statuts (édition en cours, fermeture sans changement...) n'appellent
 * aucune action ici.
 */
class CallbackController extends Controller
{
    private const STATUT_A_ENREGISTRER = [2, 6];

    public function __invoke(Request $request, string $company, string $document, TenantContext $tenantContext, DocumentUploadService $uploadService)
    {
        $payload = $request->json()->all();
        $this->verifyJwt($request, $payload);

        $tenantContext->set(Company::findOrFail($company));
        $doc = Document::query()->findOrFail($document);

        $status = (int) ($payload['status'] ?? 0);

        if (in_array($status, self::STATUT_A_ENREGISTRER, true) && ! empty($payload['url'])) {
            $this->saveEditedVersion($doc, (string) $payload['url'], $uploadService);
        }

        return response()->json(['error' => 0]);
    }

    private function saveEditedVersion(Document $document, string $downloadUrl, DocumentUploadService $uploadService): void
    {
        $currentVersion = $document->versionCourante;
        abort_if($currentVersion === null, 404);

        $extension = pathinfo($currentVersion->storage_path, PATHINFO_EXTENSION);
        $tmpPath = tempnam(sys_get_temp_dir(), 'onlyoffice_').'.'.$extension;

        try {
            file_put_contents($tmpPath, Http::get($downloadUrl)->throw()->body());

            $uploadedFile = new UploadedFile($tmpPath, $document->nom, test: true);
            // Requête serveur à serveur : aucun utilisateur authentifié dans ce contexte, on
            // attribue la nouvelle version à l'auteur de la version précédente.
            $uploadService->addVersion($document, $uploadedFile, $currentVersion->auteur, 'Modifié via édition en ligne (OnlyOffice)');
        } finally {
            @unlink($tmpPath);
        }
    }

    /** @param array<string, mixed> $payload */
    private function verifyJwt(Request $request, array $payload): void
    {
        $secret = config('ged.onlyoffice.jwt_secret');

        if (! is_string($secret) || $secret === '') {
            return;
        }

        $header = $request->header('Authorization');
        $token = $header !== null ? str_replace('Bearer ', '', $header) : ($payload['token'] ?? null);

        abort_if(! is_string($token) || $token === '', 403, 'Jeton OnlyOffice manquant.');

        try {
            JWT::decode($token, new Key($secret, 'HS256'));
        } catch (Throwable) {
            abort(403, 'Jeton OnlyOffice invalide.');
        }
    }
}
