<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Ocr\Jobs\ProcessDocumentOcr;
use App\Domain\Security\AntivirusScanFailedException;
use App\Domain\Security\AntivirusScanner;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
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

    /**
     * Extension déduite du type MIME réel quand le fichier n'a aucune extension dans son nom
     * (cas fréquent d'un PDF téléchargé/exporté sans suffixe). N'inclut que les types MIME sans
     * ambiguïté ; les formats Office modernes (.docx, .xlsx, .pptx) sont détectés par certains
     * moteurs comme "application/zip" et ne peuvent donc pas être déduits de façon fiable.
     */
    private const MIME_TO_EXTENSION = [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.ms-powerpoint' => 'ppt',
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'text/plain' => 'txt',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
    ];

    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly AntivirusScanner $antivirus,
    ) {}

    public function upload(Folder $folder, UploadedFile $file, User $author, ?string $nom = null, ?string $documentTypeId = null): Document
    {
        $extension = $this->assertFileIsAllowed($folder, $file);

        $disk = Storage::disk(config('ged.storage_disk'));
        $storagePath = $this->storeFile($disk, $folder->company_id, $file, $extension);
        $hash = hash_file('sha256', $file->getRealPath());

        $document = Document::query()->create([
            'folder_id' => $folder->id,
            'document_type_id' => $documentTypeId,
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
        $extension = $this->assertFileIsAllowed($document->folder, $file);

        $disk = Storage::disk(config('ged.storage_disk'));
        $storagePath = $this->storeFile($disk, $document->company_id, $file, $extension);
        $hash = hash_file('sha256', $file->getRealPath());

        $numero = $document->versions()->max('numero_version') + 1;

        $version = $this->createVersion($document, $disk, $storagePath, $file, $hash, $author, $numero, $commentaire);

        $document->forceFill(['version_courante_id' => $version->id])->save();

        $this->auditLogger->log($author, AuditLog::MODIFICATION, $document, ['action' => 'nouvelle_version', 'numero_version' => $numero]);

        return $version;
    }

    private function createVersion(
        Document $document,
        Filesystem $disk,
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

    private function storeFile(Filesystem $disk, string $companyId, UploadedFile $file, string $extension): string
    {
        $path = sprintf('tenants/%s/documents/%s.%s', $companyId, Str::uuid(), $extension);

        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false) {
            throw new RuntimeException('Impossible de lire le fichier téléversé.');
        }

        $disk->put($path, $stream);
        fclose($stream);

        return $path;
    }

    private function assertFileIsAllowed(Folder $folder, UploadedFile $file): string
    {
        $extension = $this->resolveExtension($file);

        if ($extension === '') {
            throw ValidationException::withMessages([
                'file' => "Impossible de déterminer le type de ce fichier : renommez-le avec son extension (par exemple .pdf) puis réessayez.",
            ]);
        }

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

        $this->assertFileIsClean($file);

        return $extension;
    }

    /**
     * L'extension déclarée par le navigateur (nom de fichier) prime quand elle existe. Si le
     * fichier n'en a aucune (nom sans suffixe), on se rabat sur le type MIME réel détecté par
     * le contenu — plus fiable qu'un nom de fichier, et ça évite de rejeter à tort un fichier
     * valide simplement parce qu'il n'a pas de ".pdf"/".docx"/etc. dans son nom.
     */
    private function resolveExtension(UploadedFile $file): string
    {
        $declared = strtolower($file->getClientOriginalExtension());
        if ($declared !== '') {
            return $declared;
        }

        return self::MIME_TO_EXTENSION[$file->getMimeType()] ?? '';
    }

    /**
     * Échec fermé : si le moteur antivirus ne peut pas rendre de verdict (démon injoignable...),
     * l'upload est refusé plutôt qu'accepté sans vérification.
     */
    private function assertFileIsClean(UploadedFile $file): void
    {
        try {
            $result = $this->antivirus->scan($file->getRealPath());
        } catch (AntivirusScanFailedException $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'file' => "Impossible de vérifier l'absence de virus pour le moment, merci de réessayer.",
            ]);
        }

        if (! $result->clean) {
            throw ValidationException::withMessages([
                'file' => "Fichier rejeté : menace détectée ({$result->menace}).",
            ]);
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
