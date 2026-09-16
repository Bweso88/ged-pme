<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Retention;

use App\Models\DocumentType;
use App\Models\RetentionPolicy;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $documentTypeId = '';

    public int $dureeConservationMois = 12;

    public string $actionAExpiration = RetentionPolicy::ACTION_DEMANDER_VALIDATION;

    public string $categorieArchive = '';

    public function mount(): void
    {
        Gate::authorize('admin.settings');
    }

    public function save(): void
    {
        $this->validate([
            'documentTypeId' => ['required', 'exists:document_types,id'],
            'dureeConservationMois' => ['required', 'integer', 'min:1'],
            'actionAExpiration' => ['required', 'in:conserver,proposer_destruction,transferer_archive,demander_validation'],
        ]);

        RetentionPolicy::query()->updateOrCreate(
            ['document_type_id' => $this->documentTypeId],
            [
                'duree_conservation_mois' => $this->dureeConservationMois,
                'action_a_expiration' => $this->actionAExpiration,
                'categorie_archive' => $this->categorieArchive ?: null,
            ],
        );

        $this->reset(['documentTypeId', 'categorieArchive']);
        $this->dureeConservationMois = 12;
    }

    public function render()
    {
        return view('livewire.admin.retention.index', [
            'policies' => RetentionPolicy::query()->with('documentType')->get(),
            'documentTypes' => DocumentType::query()->orderBy('nom')->get(),
        ]);
    }
}
