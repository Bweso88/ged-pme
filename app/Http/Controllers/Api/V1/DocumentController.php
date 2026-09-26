<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Documents\Services\DocumentSearchService;
use App\Domain\Documents\Services\DocumentUploadService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class DocumentController extends Controller
{
    public function index(Request $request, DocumentSearchService $service): AnonymousResourceCollection
    {
        $results = $service->search([
            'q' => $request->query('q'),
            'folder_id' => $request->query('folder_id'),
            'document_type_id' => $request->query('document_type_id'),
            'statut' => $request->query('statut'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
        ]);

        return DocumentResource::collection($results);
    }

    public function store(Request $request, DocumentUploadService $service): JsonResponse
    {
        Gate::authorize('create', Document::class);

        $data = $request->validate([
            'folder_id' => ['required', 'exists:folders,id'],
            'file' => ['required', 'file'],
            'nom' => ['nullable', 'string', 'max:255'],
            'document_type_id' => ['nullable', 'exists:document_types,id'],
        ]);

        $folder = Folder::query()->findOrFail($data['folder_id']);
        Gate::authorize('view', $folder);

        $document = $service->upload(
            $folder,
            $request->file('file'),
            Auth::user(),
            $data['nom'] ?? null,
            $data['document_type_id'] ?? null,
        );

        $document->load(['documentType', 'auteur', 'versionCourante']);

        return (new DocumentResource($document))->response()->setStatusCode(201);
    }

    public function show(Document $document): DocumentResource
    {
        Gate::authorize('view', $document);

        $document->load(['documentType', 'auteur', 'versionCourante']);

        return new DocumentResource($document);
    }
}
