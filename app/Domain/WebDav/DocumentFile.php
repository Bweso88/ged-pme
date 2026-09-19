<?php

declare(strict_types=1);

namespace App\Domain\WebDav;

use App\Domain\Documents\Services\DocumentUploadService;
use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\File;

/**
 * Nœud WebDAV représentant un document (sa version courante). En lecture : identique au
 * téléchargement classique (même vérification de permission). En écriture (PUT, ex. "Ctrl+S"
 * depuis Word ouvert sur le lecteur réseau) : crée une nouvelle version via le service d'upload
 * habituel — donc avec le même passage par l'antivirus, le même contrôle de type/taille, et le
 * même job OCR — jamais une écriture "brute" du fichier stocké.
 */
class DocumentFile extends File
{
    public function __construct(
        private readonly WebDavContext $context,
        private readonly Document $document,
        private readonly string $name,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function get()
    {
        $this->authorize('download');

        $version = $this->document->versionCourante;
        abort_unless($version !== null, 404);

        return Storage::disk(config('ged.storage_disk'))->readStream($version->storage_path);
    }

    public function put($data): ?string
    {
        $this->authorize('createVersion');

        $tmpPath = tempnam(sys_get_temp_dir(), 'yosefa_webdav_');
        $handle = fopen($tmpPath, 'wb');
        stream_copy_to_stream(is_resource($data) ? $data : $this->stringToStream((string) $data), $handle);
        fclose($handle);

        try {
            $uploadedFile = new UploadedFile($tmpPath, $this->name, mimeType: null, error: null, test: true);
            app(DocumentUploadService::class)->addVersion(
                $this->document,
                $uploadedFile,
                $this->context->user(),
                'Modifié depuis le lecteur réseau (WebDAV)',
            );
        } finally {
            @unlink($tmpPath);
        }

        return null;
    }

    public function getSize(): int
    {
        return (int) ($this->document->versionCourante?->taille_octets ?? 0);
    }

    public function getETag(): ?string
    {
        $hash = $this->document->versionCourante?->hash_sha256;

        return $hash !== null ? '"'.$hash.'"' : null;
    }

    public function getContentType(): ?string
    {
        return $this->document->versionCourante?->mime_type;
    }

    public function getLastModified(): ?int
    {
        return $this->document->versionCourante?->created_at?->getTimestamp();
    }

    private function authorize(string $ability): void
    {
        if (! Gate::forUser($this->context->user())->allows($ability, $this->document)) {
            throw new Forbidden("Action non autorisée sur « {$this->document->nom} ».");
        }
    }

    /** @return resource */
    private function stringToStream(string $data)
    {
        $stream = fopen('php://temp', 'r+b');
        fwrite($stream, $data);
        rewind($stream);

        return $stream;
    }
}
