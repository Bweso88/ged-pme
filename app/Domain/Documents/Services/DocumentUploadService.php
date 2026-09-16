<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Ocr\Jobs\ProcessDocumentOcr;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Dépose un nouveau document (ou une nouvelle version) : validation stricte du fichier
 * (extension + type MIME réel), stockage sous un nom généré (jamais dérivé du nom d'origine),
 * calcul du hash d'intégrité, puis déclenchement de l'OCR en tâche de fond si applicable.
 *
 * Voir doc ged-pme/docs/04-securite-workflow-archivage.md, §11.2 et
 * ged-pme/docs/02-architecture-technique.md, §10.3.
 */
class DocumentUploadService
{
    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'png', 'jpg', 'jpeg', 'txt', 'zip'];

    private const OCR_ELIGIBLE_MIME_TYPES = ['application/pdf', 'image/png', 'image/jpeg'];

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function upload(Folder $folder, UploadedFile $file, User $author, ?string $nom = null): Document
    {
        $this->assertFileIsAllowed($folder, $file);

        $disk = Storage::disk(config('ged.storage_disk'));
        $storagePath = $this->storeFile($disk, $folder->company_id, $file);
        $hash = hash_file('sha256', $file->getRealPath());

        $document = Document::query()->create([
            'folder_id' => $folder->id,
            'nom' => $nom ?? $file->getClientOriginalName(),
            'statut' => Document::STATUT_BROUILLON,
            'auteur_id' => $author->id,
            'proprietaire_id' => $author->id,
        ]);

        $version = $this->createVersion($document, $disk, $storagePath, $file, $hash, $author, 1);

        $document->forceFill(['version_courante_id' => $version->id])->save();

        $this->auditLogger->log($author, AuditLog::CREATION, $document);

        return $document->refresh();
    }

    public function addVersion(Document $document, UploadedFile $file, User $author, ?string $commentaire = null): DocumentVersion
    {
        $this->assertFileIsAllowed($document->folder, $file);

        $disk = Storage::disk(config('ged.storage_disk'));
        $storagePath = $this->storeFile($disk, $document->company_id, $file);
        $hash = hash_file('sha256', $file->getRealPath());

        $numero = $document->versions()->max('numero_version') + 1;

        $version = $this->createVersion($document, $disk, $storagePath, $file, $hash, $author, $numero, $commentaire);

        $document->forceFill(['version_courante_id' => $version->id])->save();

        $this->auditLogger->log($author, AuditLog::MODIFICATION, $document, ['action' => 'nouvelle_version', 'numero_version' => $numero]);

        return $version;
    }

    private function createVersion(
        Document $document,
        \Illuminate\Contracts\Filesystem\Filesystem $disk,
        string $storagePath,
        UploadedFile $file,
        string $hash,
        User $author,
        int $numero,
        ?string $commentaire = null,
    ): DocumentVersion {
        $mimeType = $file->getMimeType() ?? 'application/octet-stream';
        $ocrEligible = in_array($mimeType, self::OCR_ELIGIBLE_MIME_TYPES, true);

        $version = DocumentVersion::query()->create([
            'document_id' => $document->id,
            'numero_version' => $numero,
            'storage_path' => $storagePath,
            'taille_octets' => $file->getSize(),
            'hash_sha256' => $hash,
            'mime_type' => $mimeType,
            'ocr_statut' => $ocrEligible ? DocumentVersion::OCR_EN_ATTENTE : DocumentVersion::OCR_NON_REQUIS,
            'auteur_id' => $author->id,
            'commentaire' => $commentaire,
        ]);

        if ($ocrEligible) {
            ProcessDocumentOcr::dispatch($version->id);
        }

        return $version;
    }

    private function storeFile(\Illuminate\Contracts\Filesystem\Filesystem $disk, string $companyId, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = sprintf('tenants/%s/documents/%s.%s', $companyId, Str::uuid(), $extension);

        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false) {
            throw new RuntimeException('Impossible de lire le fichier téléversé.');
        }

        $disk->put($path, $stream);
        fclose($stream);

        return $path;
    }

    private function assertFileIsAllowed(Folder $folder, UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages(['file' => "Extension .{$extension} non autorisée."]);
        }

        $maxBytes = (int) config('ged.max_upload_mb') * 1024 * 1024;
        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages(['file' => 'Le fichier dépasse la taille maximale autorisée.']);
        }

        // Le type MIME réel (détecté par le contenu, pas par l'extension déclarée) doit être
        // cohérent avec l'extension : empêche un exécutable renommé en ".pdf".
        $detectedMime = $file->getMimeType();
        if ($detectedMime !== null && ! $this->mimeMatchesExtension($detectedMime, $extension)) {
            throw ValidationException::withMessages(['file' => 'Le contenu du fichier ne correspond pas à son extension.']);
        }
    }

    private function mimeMatchesExtension(string $mime, string $extension): bool
    {
        $expected = [
            'pdf' => ['application/pdf'],
            'doc' => ['application/msword'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
            'xls' => ['application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'ppt' => ['application/vnd.ms-powerpoint'],
            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip'],
            'png' => ['image/png'],
            'jpg' => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'txt' => ['text/plain'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
        ];

        return in_array($mime, $expected[$extension] ?? [], true);
    }
}
