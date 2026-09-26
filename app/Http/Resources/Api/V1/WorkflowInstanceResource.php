<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WorkflowInstance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WorkflowInstance */
class WorkflowInstanceResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'statut' => $this->statut,
            'document' => new DocumentResource($this->whenLoaded('document')),
            'etape_courante' => $this->whenLoaded('etapeCourante', fn () => $this->etapeCourante === null ? null : [
                'id' => $this->etapeCourante->id,
                'nom' => $this->etapeCourante->nom,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
