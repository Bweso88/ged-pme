<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\DocumentVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentVersion */
class DocumentVersionResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_version' => $this->numero_version,
            'mime_type' => $this->mime_type,
            'taille_octets' => $this->taille_octets,
            'commentaire' => $this->commentaire,
            'auteur' => $this->whenLoaded('auteur', fn () => [
                'id' => $this->auteur->id,
                'nom' => $this->auteur->name,
            ]),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
