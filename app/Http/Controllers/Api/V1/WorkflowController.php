<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Workflow\Services\WorkflowService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Document;
use App\Models\WorkflowInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class WorkflowController extends Controller
{
    public function submit(Document $document, WorkflowService $service): DocumentResource
    {
        Gate::authorize('submitWorkflow', $document);

        $service->submit($document, Auth::user());

        $document->refresh()->load(['documentType', 'auteur', 'versionCourante']);

        return new DocumentResource($document);
    }

    public function approve(Request $request, Document $document, WorkflowService $service): DocumentResource
    {
        $data = $request->validate(['commentaire' => ['nullable', 'string']]);

        $instance = $this->currentInstance($document);
        Gate::authorize('validateWorkflow', $document);

        $service->approve($instance, Auth::user(), $data['commentaire'] ?? null);

        $document->refresh()->load(['documentType', 'auteur', 'versionCourante']);

        return new DocumentResource($document);
    }

    public function reject(Request $request, Document $document, WorkflowService $service): DocumentResource
    {
        $data = $request->validate(['commentaire' => ['required', 'string']]);

        $instance = $this->currentInstance($document);
        Gate::authorize('validateWorkflow', $document);

        $service->reject($instance, Auth::user(), $data['commentaire']);

        $document->refresh()->load(['documentType', 'auteur', 'versionCourante']);

        return new DocumentResource($document);
    }

    public function requestChanges(Request $request, Document $document, WorkflowService $service): DocumentResource
    {
        $data = $request->validate(['commentaire' => ['required', 'string']]);

        $instance = $this->currentInstance($document);
        Gate::authorize('validateWorkflow', $document);

        $service->requestChanges($instance, Auth::user(), $data['commentaire']);

        $document->refresh()->load(['documentType', 'auteur', 'versionCourante']);

        return new DocumentResource($document);
    }

    private function currentInstance(Document $document): WorkflowInstance
    {
        $instance = $document->currentWorkflowInstance();

        abort_if($instance === null, 404);

        return $instance;
    }
}
