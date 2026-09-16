<?php

declare(strict_types=1);

namespace App\Http\Controllers\Documents;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Aucune URL de stockage brute n'est jamais exposée (voir doc 02, §9.3) : ce contrôleur
 * vérifie la policy, journalise l'accès puis stream le fichier depuis le disque configuré.
 */
class DownloadController extends Controller
{
    public function __invoke(Request $request, Document $document, AuditLogger $auditLogger): StreamedResponse
    {
        Gate::authorize('download', $document);

        $version = $document->versionCourante ?? $document->versions()->first();
        abort_if($version === null, 404);

        $auditLogger->log($request->user(), AuditLog::TELECHARGEMENT, $document);

        $disk = Storage::disk(config('ged.storage_disk'));

        return $disk->response($version->storage_path, $document->nom, [
            'Content-Type' => $version->mime_type,
        ]);
    }
}
