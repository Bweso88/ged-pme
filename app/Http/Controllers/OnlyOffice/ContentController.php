<?php

declare(strict_types=1);

namespace App\Http\Controllers\OnlyOffice;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Document;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Storage;

/**
 * Sert le contenu du document au Document Server OnlyOffice (requête serveur à serveur, sans
 * session Laravel — l'autorisation vient de la signature de l'URL, voir OnlyOfficeConfigService
 * et la middleware "signed"). {company} amorce le contexte tenant avant toute lecture soumise à
 * la Row-Level Security PostgreSQL, qui bloquerait sinon tout accès (voir App\Domain\WebDav\AuthBackend
 * pour le même besoin côté WebDAV).
 */
class ContentController extends Controller
{
    public function __invoke(string $company, string $document, TenantContext $tenantContext)
    {
        $tenantContext->set(Company::findOrFail($company));

        $doc = Document::query()->findOrFail($document);
        $version = $doc->versionCourante;
        abort_if($version === null, 404);

        return Storage::disk(config('ged.storage_disk'))->response($version->storage_path, $doc->nom);
    }
}
