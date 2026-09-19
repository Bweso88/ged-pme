<?php

declare(strict_types=1);

namespace App\Domain\OnlyOffice;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\URL;

/**
 * Construit la configuration transmise au script JS d'OnlyOffice (DocsAPI.DocEditor), signée en
 * JWT si un secret est configuré (fortement recommandé en production — sans secret, n'importe
 * qui connaissant l'URL du Document Server pourrait forger une config pointant vers un autre
 * document).
 */
class OnlyOfficeConfigService
{
    public function buildEditorConfig(Document $document, User $user): array
    {
        $version = $document->versionCourante;
        abort_if($version === null, 404);

        $extension = pathinfo($version->storage_path, PATHINFO_EXTENSION);
        abort_unless(OnlyOfficeSupport::isEditable($extension), 422, 'Ce type de document ne peut pas être édité en ligne.');

        $config = [
            'document' => [
                'fileType' => $extension,
                'key' => $this->documentKey($version),
                'title' => $document->nom,
                'url' => $this->externalUrl($this->contentUrl($document)),
                'permissions' => ['edit' => true, 'download' => true],
            ],
            'documentType' => OnlyOfficeSupport::documentType($extension),
            'editorConfig' => [
                'callbackUrl' => $this->externalUrl($this->callbackUrl($document)),
                'user' => ['id' => (string) $user->id, 'name' => $user->name],
                'lang' => 'fr',
            ],
        ];

        $secret = config('ged.onlyoffice.jwt_secret');
        if (is_string($secret) && $secret !== '') {
            $config['token'] = JWT::encode($config, $secret, 'HS256');
        }

        return $config;
    }

    /** Change à chaque nouvelle version pour qu'OnlyOffice ne serve jamais un contenu en cache périmé. */
    private function documentKey(DocumentVersion $version): string
    {
        return substr(hash('sha256', $version->id.'-'.$version->updated_at?->timestamp), 0, 32);
    }

    private function contentUrl(Document $document): string
    {
        return URL::temporarySignedRoute('onlyoffice.content', now()->addMinutes(30), [
            'company' => $document->company_id,
            'document' => $document->id,
        ]);
    }

    private function callbackUrl(Document $document): string
    {
        return URL::temporarySignedRoute('onlyoffice.callback', now()->addHours(6), [
            'company' => $document->company_id,
            'document' => $document->id,
        ]);
    }

    /**
     * Le Document Server (pas le navigateur de l'utilisateur) doit pouvoir atteindre ces URLs :
     * si les deux ne partagent pas le même nom d'hôte (ex. réseau Docker interne), on substitue
     * l'hôte configuré pour lui.
     */
    private function externalUrl(string $url): string
    {
        $internal = config('ged.onlyoffice.internal_app_url');

        if (! is_string($internal) || $internal === '') {
            return $url;
        }

        return preg_replace('#^https?://[^/]+#', rtrim($internal, '/'), $url) ?? $url;
    }
}
