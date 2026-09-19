<?php

declare(strict_types=1);

namespace App\Livewire\Admin\DocumentTypes;

use App\Models\DocumentType;
use App\Models\MetadataField;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public string $nom = '';

    public string $code = '';

    public ?int $dureeConservationMois = null;

    public ?string $editingTypeId = null;

    public string $fieldLabel = '';

    public string $fieldCode = '';

    public string $fieldType = MetadataField::TYPE_TEXTE;

    public bool $fieldObligatoire = false;

    public string $fieldExtractionPattern = '';

    public function mount(): void
    {
        Gate::authorize('admin.settings');
    }

    public function createType(): void
    {
        $this->validate([
            'nom' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100'],
        ]);

        DocumentType::query()->create([
            'nom' => $this->nom,
            'code' => $this->code,
            'duree_conservation_mois' => $this->dureeConservationMois,
        ]);

        $this->reset(['nom', 'code', 'dureeConservationMois']);
    }

    public function manageFields(string $typeId): void
    {
        $this->editingTypeId = $typeId;
    }

    public function addField(): void
    {
        $this->validate([
            'fieldLabel' => ['required', 'string', 'max:255'],
            'fieldCode' => ['required', 'string', 'max:100'],
            'fieldType' => ['required', 'in:texte,nombre,date,liste,booleen'],
        ]);

        MetadataField::query()->create([
            'document_type_id' => $this->editingTypeId,
            'code' => $this->fieldCode,
            'label' => $this->fieldLabel,
            'type' => $this->fieldType,
            'obligatoire' => $this->fieldObligatoire,
            'extraction_pattern' => $this->fieldExtractionPattern ?: null,
        ]);

        $this->reset(['fieldLabel', 'fieldCode', 'fieldObligatoire', 'fieldExtractionPattern']);
        $this->fieldType = MetadataField::TYPE_TEXTE;
    }

    public function render()
    {
        return view('livewire.admin.document-types.index', [
            'types' => DocumentType::query()->orderBy('nom')->get(),
            'editingFields' => $this->editingTypeId
                ? MetadataField::query()->where('document_type_id', $this->editingTypeId)->orderBy('ordre')->get()
                : collect(),
        ]);
    }
}
