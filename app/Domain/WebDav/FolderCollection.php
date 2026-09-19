<?php

declare(strict_types=1);

namespace App\Domain\WebDav;

use App\Models\Folder;
use Illuminate\Support\Facades\Gate;
use Sabre\DAV\Collection;

/**
 * Nœud WebDAV représentant un dossier YOSEFA (ou la racine si $folder est null). Respecte les
 * mêmes permissions que l'explorateur web (Gate::allows('view', ...)) — un dossier ou document
 * non autorisé n'apparaît simplement pas dans le listing, comme ailleurs dans l'application.
 */
class FolderCollection extends Collection
{
    public function __construct(
        private readonly WebDavContext $context,
        private readonly ?Folder $folder,
        private readonly string $name,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /** @return array<int, Collection|\Sabre\DAV\File> */
    public function getChildren(): array
    {
        $user = $this->context->user();
        $children = [];

        $subfoldersQuery = Folder::query();
        if ($this->folder === null) {
            $subfoldersQuery->whereNull('parent_id');
        } else {
            $subfoldersQuery->where('parent_id', $this->folder->id);
        }

        foreach ($subfoldersQuery->orderBy('nom')->get() as $subfolder) {
            if (Gate::forUser($user)->allows('view', $subfolder)) {
                $children[] = new self($this->context, $subfolder, self::sanitizeName($subfolder->nom));
            }
        }

        if ($this->folder !== null) {
            foreach ($this->folder->documents()->whereNotNull('version_courante_id')->orderBy('nom')->get() as $document) {
                if (Gate::forUser($user)->allows('view', $document)) {
                    $children[] = new DocumentFile($this->context, $document, self::sanitizeName($document->nom));
                }
            }
        }

        return $children;
    }

    public function getLastModified(): ?int
    {
        return $this->folder?->updated_at?->getTimestamp();
    }

    /**
     * Un nom de dossier/document peut contenir des caractères invalides dans un chemin WebDAV
     * (le "/" en premier lieu). On les remplace plutôt que de faire échouer le montage.
     */
    public static function sanitizeName(string $name): string
    {
        $sanitized = str_replace('/', '-', trim($name));

        return $sanitized === '' ? '(sans nom)' : $sanitized;
    }
}
