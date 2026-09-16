<?php

declare(strict_types=1);

namespace App\Livewire\Archives;

use App\Domain\Archiving\Services\ArchiveService;
use App\Models\ArchiveRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $motif = '';

    public function validateDestruction(string $recordId): void
    {
        Gate::authorize('admin.settings');
        $record = ArchiveRecord::query()->findOrFail($recordId);

        $this->validate(['motif' => ['required', 'string']], ['motif.required' => 'Un motif est requis pour valider une destruction.']);

        app(ArchiveService::class)->validateDestruction($record, Auth::user(), $this->motif);
        $this->motif = '';
    }

    public function extend(string $recordId): void
    {
        Gate::authorize('admin.settings');
        $record = ArchiveRecord::query()->findOrFail($recordId);

        app(ArchiveService::class)->extendRetention($record, Auth::user(), 12);
    }

    public function render()
    {
        return view('livewire.archives.index', [
            'active' => ArchiveRecord::query()->where('statut', ArchiveRecord::STATUT_ACTIF)->with('document')->get(),
            'proposed' => ArchiveRecord::query()->where('statut', ArchiveRecord::STATUT_PROPOSE_DESTRUCTION)->with('document')->get(),
        ]);
    }
}
