<?php

declare(strict_types=1);

namespace App\Http\Controllers\OnlyOffice;

use App\Domain\OnlyOffice\OnlyOfficeConfigService;
use App\Http\Controllers\Controller;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EditorController extends Controller
{
    public function __invoke(Document $document, OnlyOfficeConfigService $configService)
    {
        Gate::authorize('createVersion', $document);
        abort_unless((bool) config('ged.onlyoffice.enabled'), 404);

        return view('onlyoffice.editor', [
            'document' => $document,
            'documentServerUrl' => rtrim((string) config('ged.onlyoffice.document_server_url'), '/'),
            'editorConfig' => $configService->buildEditorConfig($document, Auth::user()),
        ]);
    }
}
