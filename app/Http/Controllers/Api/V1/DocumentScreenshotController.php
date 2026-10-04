<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/**
 * Reçoit le signal envoyé par l'app mobile iOS quand une capture d'écran a été prise pendant
 * la consultation d'un document — iOS ne permet pas de bloquer la capture (contrairement à
 * Android, protégé en amont par FLAG_SECURE), seulement d'en être notifié après coup. Au
 * minimum, l'événement reste tracé dans le journal d'audit avec son auteur et l'horodatage.
 */
class DocumentScreenshotController extends Controller
{
    public function __invoke(Request $request, Document $document, AuditLogger $auditLogger): Response
    {
        Gate::authorize('view', $document);

        $auditLogger->log($request->user(), AuditLog::CAPTURE_ECRAN, $document, [
            'plateforme' => 'ios',
        ]);

        return response()->noContent();
    }
}
