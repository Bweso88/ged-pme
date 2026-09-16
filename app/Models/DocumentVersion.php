<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Tenancy\BelongsToCompany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use BelongsToCompany, HasUuids;

    public const OCR_NON_REQUIS = 'non_requis';

    public const OCR_EN_ATTENTE = 'en_attente';

    public const OCR_TERMINE = 'termine';

    public const OCR_ECHEC = 'echec';

    protected $fillable = [
        'company_id', 'document_id', 'numero_version', 'storage_path', 'taille_octets',
        'hash_sha256', 'mime_type', 'texte_ocr', 'ocr_statut', 'auteur_id', 'commentaire',
    ];

    protected function casts(): array
    {
        return [
            'numero_version' => 'integer',
            'taille_octets' => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }
}
