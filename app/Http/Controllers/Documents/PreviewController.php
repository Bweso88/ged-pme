<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documents;

use App\Domain\Documents\Support\PreviewSupport;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Aperçu en ligne d'un document, distinct du téléchargement (voir DownloadController) :
 * accessible avec le seul droit "document.view", jamais "document.download". Le fichier est
 * streamé avec Content-Disposition: inline pour s'afficher dans le navigateur/l'app plutôt
 * que de proposer un enregistrement, et limité aux types que l'on sait afficher nous-mêmes
 * (image, PDF) — les autres types renvoient 415 pour que l'interface affiche un message
 * plutôt qu'un fichier corrompu.
 */
class PreviewController extends Controller
{
    public function __invoke(Document $document): StreamedResponse|Response
    {
        Gate::authorize('view', $document);

        $version = $document->versionCourante ?? $document->versions()->first();
        abort_if($version === null, 404);

        if (! PreviewSupport::isPreviewable($version->mime_type)) {
            abort(415, "Aperçu indisponible pour ce type de fichier.");
        }

        $disk = Storage::disk(config('ged.storage_disk'));

        return $disk->response($version->storage_path, $document->nom, [
            'Content-Type' => $version->mime_type,
            'Content-Disposition' => 'inline; filename="'.addslashes($document->nom).'"',
        ]);
    }
}
