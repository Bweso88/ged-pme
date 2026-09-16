<?php

declare(strict_types=1);

namespace App\Livewire\Documents;

use App\Domain\Archiving\Services\ArchiveService;
use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Documents\Services\DocumentUploadService;
use App\Domain\Documents\Services\MetadataService;
use App\Domain\Documents\Services\TrashService;
use App\Domain\Sharing\Services\ShareService;
use App\Domain\Workflow\Services\WorkflowService;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Show extends Component
{
    use WithFileUploads;

    public Document $document;

    public array $metadata = [];

    public $newVersion = null;

    public string $newVersionComment = '';

    public string $decisionComment = '';

    public string $shareEmail = '';

    public string $shareExpiresAt = '';

    public string $sharePassword = '';

    public bool $showArchiveForm = false;

    public string $archiveMotif = '';

    public string $archiveConfidentialite = 'interne';

    public function mount(Document $document): void
    {
        Gate::authorize('view', $document);

        $this->document = $document;
        $this->metadata = app(MetadataService::class)->valuesFor($document);

        app(AuditLogger::class)->log(Auth::user(), AuditLog::CONSULTATION, $document);
    }

    public function saveMetadata(): void
    {
        Gate::authorize('update', $this->document);

        app(MetadataService::class)->save($this->document, $this->metadata);
        $this->dispatch('$refresh');
    }

    public function uploadNewVersion(): void
    {
        Gate::authorize('createVersion', $this->document);

        $this->validate(['newVersion' => ['required', 'file']]);

        app(DocumentUploadService::class)->addVersion($this->document, $this->newVersion, Auth::user(), $this->newVersionComment ?: null);

        $this->newVersion = null;
        $this->newVersionComment = '';
        $this->document->refresh();
    }

    public function restoreVersion(string $versionId): void
    {
        Gate::authorize('restoreVersion', $this->document);

        $version = $this->document->versions()->findOrFail($versionId);

        $this->document->forceFill(['version_courante_id' => $version->id])->save();
        app(AuditLogger::class)->log(Auth::user(), AuditLog::RESTAURATION, $this->document, ['version' => $version->numero_version]);
    }

    public function download(): void
    {
        Gate::authorize('download', $this->document);
        app(AuditLogger::class)->log(Auth::user(), AuditLog::TELECHARGEMENT, $this->document);

        $this->dispatch('trigger-download', url: route('documents.download', $this->document));
    }

    public function submitWorkflow(): void
    {
        Gate::authorize('submitWorkflow', $this->document);
        app(WorkflowService::class)->submit($this->document, Auth::user());
        $this->document->refresh();
    }

    public function approveWorkflow(): void
    {
        $instance = $this->currentWorkflowInstance();
        Gate::authorize('validateWorkflow', $this->document);
        app(WorkflowService::class)->approve($instance, Auth::user(), $this->decisionComment ?: null);
        $this->decisionComment = '';
        $this->document->refresh();
    }

    public function rejectWorkflow(): void
    {
        $this->validate(['decisionComment' => ['required', 'string']], ['decisionComment.required' => 'Un motif de rejet est requis.']);

        $instance = $this->currentWorkflowInstance();
        Gate::authorize('validateWorkflow', $this->document);
        app(WorkflowService::class)->reject($instance, Auth::user(), $this->decisionComment);
        $this->decisionComment = '';
        $this->document->refresh();
    }

    public function requestChangesWorkflow(): void
    {
        $this->validate(['decisionComment' => ['required', 'string']], ['decisionComment.required' => 'Merci de préciser la modification attendue.']);

        $instance = $this->currentWorkflowInstance();
        Gate::authorize('validateWorkflow', $this->document);
        app(WorkflowService::class)->requestChanges($instance, Auth::user(), $this->decisionComment);
        $this->decisionComment = '';
        $this->document->refresh();
    }

    private function currentWorkflowInstance()
    {
        $instance = $this->document->currentWorkflowInstance();

        abort_if($instance === null, 404);

        return $instance;
    }

    public function shareByEmail(): void
    {
        Gate::authorize('share', $this->document);
        $this->validate(['shareEmail' => ['required', 'email']]);

        $target = User::where('email', $this->shareEmail)->where('company_id', $this->document->company_id)->first();

        if ($target === null) {
            $this->addError('shareEmail', 'Aucun utilisateur de votre entreprise ne correspond à cet e-mail.');

            return;
        }

        app(ShareService::class)->shareWithUser($this->document, $target, Auth::user());
        $this->shareEmail = '';
        $this->document->refresh();
    }

    public function createShareLink(): void
    {
        Gate::authorize('share', $this->document);
        $this->validate(['shareExpiresAt' => ['required', 'date', 'after:now']]);

        app(ShareService::class)->createLink(
            $this->document,
            Auth::user(),
            \Illuminate\Support\Carbon::parse($this->shareExpiresAt),
            $this->sharePassword ?: null,
        );

        $this->shareExpiresAt = '';
        $this->sharePassword = '';
        $this->document->refresh();
    }

    public function revokeShare(string $shareId): void
    {
        Gate::authorize('share', $this->document);
        $share = $this->document->shares()->findOrFail($shareId);
        app(ShareService::class)->revoke($share, Auth::user());
        $this->document->refresh();
    }

    public function archive(): void
    {
        Gate::authorize('archive', $this->document);
        app(ArchiveService::class)->archive($this->document, Auth::user(), null, $this->archiveConfidentialite, $this->archiveMotif ?: null);
        $this->showArchiveForm = false;
        $this->document->refresh();
    }

    public function moveToTrash(): void
    {
        Gate::authorize('trash', $this->document);
        app(TrashService::class)->moveToTrash($this->document, Auth::user());

        $this->redirect(route('documents.show-folder', $this->document->folder), navigate: true);
    }

    public function render()
    {
        return view('livewire.documents.show', [
            'metadataFields' => app(MetadataService::class)->fieldsFor($this->document),
            'workflowInstance' => $this->document->currentWorkflowInstance(),
        ]);
    }
}
