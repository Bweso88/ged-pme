<?php

declare(strict_types=1);

namespace App\Livewire\Trash;

use App\Domain\Documents\Services\TrashService;
use App\Models\Document;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public function restore(string $documentId): void
    {
        $document = Document::onlyTrashed()->findOrFail($documentId);
        Gate::authorize('restoreFromTrash', $document);

        app(TrashService::class)->restore($document, Auth::user());
    }

    public function forceDelete(string $documentId): void
    {
        $document = Document::onlyTrashed()->findOrFail($documentId);
        Gate::authorize('forceDelete', $document);

        app(TrashService::class)->forceDelete($document, Auth::user());
    }

    public function render()
    {
        return view('livewire.trash.index', [
            'documents' => Document::onlyTrashed()->with('folder', 'trashedBy')->orderByDesc('trashed_at')->get(),
        ]);
    }
}
