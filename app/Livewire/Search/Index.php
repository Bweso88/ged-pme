<?php

declare(strict_types=1);

namespace App\Livewire\Search;

use App\Domain\Documents\Services\DocumentSearchService;
use App\Models\DocumentType;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $q = '';

    public string $documentTypeId = '';

    public string $statut = '';

    public string $reference = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $results = app(DocumentSearchService::class)->search([
            'q' => $this->q,
            'document_type_id' => $this->documentTypeId ?: null,
            'statut' => $this->statut ?: null,
            'reference' => $this->reference ?: null,
            'date_from' => $this->dateFrom ?: null,
            'date_to' => $this->dateTo ?: null,
        ]);

        return view('livewire.search.index', [
            'results' => $results,
            'documentTypes' => DocumentType::query()->orderBy('nom')->get(),
        ]);
    }
}
