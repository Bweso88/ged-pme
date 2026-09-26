<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Documents\Services\DocumentUploadService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DocumentVersionController extends Controller
{
    public function store(Request $request, Document $document, DocumentUploadService $service): DocumentResource
    {
        Gate::authorize('createVersion', $document);

        $data = $request->validate([
            'file' => ['required', 'file'],
            'commentaire' => ['nullable', 'string'],
        ]);

        $service->addVersion($document, $request->file('file'), Auth::user(), $data['commentaire'] ?? null);

        $document->refresh()->load(['documentType', 'auteur', 'versionCourante']);

        return new DocumentResource($document);
    }
}
