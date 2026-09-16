<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sharing;

use App\Domain\Sharing\Services\ShareService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Accès public contrôlé à un lien de partage (voir doc 05, écran 30). Aucune authentification
 * requise, mais le lien doit être actif (non révoqué, non expiré) et le mot de passe, s'il est
 * défini, doit être fourni. Chaque tentative de mot de passe erroné est simplement rejetée
 * (pas d'énumération de documents possible : le token est un secret de 48 caractères aléatoires).
 */
class ShareLinkController extends Controller
{
    public function __invoke(Request $request, string $token, ShareService $shareService): StreamedResponse|RedirectResponse
    {
        try {
            $share = $shareService->resolveLink($token, $request->query('password'));
        } catch (ValidationException $e) {
            return response()->view('shares.password', [
                'token' => $token,
                'error' => $e->getMessage(),
            ])->withErrors($e->errors());
        }

        $document = $share->document;
        $version = $document->versionCourante ?? $document->versions()->first();
        abort_if($version === null, 404);

        $disk = Storage::disk(config('ged.storage_disk'));

        return $disk->response($version->storage_path, $document->nom, [
            'Content-Type' => $version->mime_type,
        ]);
    }
}
