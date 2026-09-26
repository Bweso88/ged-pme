<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
class DocumentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'reference' => $this->reference,
            'statut' => $this->statut,
            'confidentialite' => $this->confidentialite,
            'folder_id' => $this->folder_id,
            'document_type' => $this->whenLoaded('documentType', fn () => $this->documentType === null ? null : new DocumentTypeResource($this->documentType)),
            'auteur' => $this->whenLoaded('auteur', fn () => [
                'id' => $this->auteur->id,
                'nom' => $this->auteur->name,
            ]),
            'version_courante' => $this->whenLoaded('versionCourante', fn () => $this->versionCourante === null ? null : new DocumentVersionResource($this->versionCourante)),
            'date_document' => $this->date_document?->toDateString(),
            'date_expiration' => $this->date_expiration?->toDateString(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
