<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Domain\Documents\Services\DocumentUploadService;
use App\Domain\Documents\Services\FolderService;
use App\Models\Document;
use App\Models\Folder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Explorer extends Component
{
    use WithFileUploads;

    public ?string $folderId = null;

    public bool $showNewFolder = false;

    public string $newFolderName = '';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $uploads = [];

    public function mount(?Folder $folder = null): void
    {
        $this->folderId = $folder?->id;
    }

    private function currentFolder(): ?Folder
    {
        return $this->folderId !== null ? Folder::query()->findOrFail($this->folderId) : null;
    }

    /** @return list<Folder> */
    private function breadcrumb(?Folder $folder): array
    {
        $crumbs = [];

        while ($folder !== null) {
            $crumbs[] = $folder;
            $folder = $folder->parent;
        }

        return array_reverse($crumbs);
    }

    public function createFolder(): void
    {
        $this->validate(['newFolderName' => ['required', 'string', 'max:255']]);

        Gate::authorize('create', Folder::class);

        app(FolderService::class)->create($this->currentFolder(), $this->newFolderName, Auth::user());

        $this->newFolderName = '';
        $this->showNewFolder = false;
    }

    public function updatedUploads(): void
    {
        $folder = $this->currentFolder();

        if ($folder === null) {
            $this->addError('uploads', 'Sélectionnez un dossier avant d\'importer un document.');

            return;
        }

        Gate::authorize('create', Document::class);

        $service = app(DocumentUploadService::class);

        foreach ($this->uploads as $upload) {
            $service->upload($folder, $upload, Auth::user());
        }

        $this->uploads = [];
    }

    public function render()
    {
        $folder = $this->currentFolder();

        return view('livewire.documents.explorer', [
            'folder' => $folder,
            'breadcrumb' => $this->breadcrumb($folder),
            'subfolders' => Folder::query()->where('parent_id', $this->folderId)->orderBy('nom')->get(),
            'documents' => $folder !== null
                ? Document::query()->where('folder_id', $folder->id)->orderByDesc('updated_at')->get()
                : collect(),
        ]);
    }
}
