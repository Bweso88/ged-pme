<?php

declare(strict_types=1);

namespace App\Domain\WebDav;

use Sabre\HTTP\ResponseInterface;
use Sabre\HTTP\Sapi;

/**
 * Sabre\HTTP\Sapi::sendResponse() écrit directement dans la sortie PHP (header()/echo), ce qui
 * est incompatible avec le cycle de réponse Laravel/Symfony. On neutralise cet envoi direct : le
 * contrôleur WebDAV lit lui-même le contenu de la réponse Sabre pour construire une vraie
 * réponse Laravel (voir App\Http\Controllers\WebDav\WebDavController).
 */
class SilentSapi extends Sapi
{
    public static function sendResponse(ResponseInterface $response) {}
}
