<?php

declare(strict_types=1);

namespace App\Http\Controllers\WebDav;

use App\Domain\WebDav\AuthBackend;
use App\Domain\WebDav\FolderCollection;
use App\Domain\WebDav\SilentSapi;
use App\Domain\WebDav\WebDavContext;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response as LaravelResponse;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Server;
use Sabre\HTTP\Request as SabreRequest;
use Sabre\HTTP\Response as SabreResponse;

/**
 * Point d'entrée unique du serveur WebDAV (voir docs/07-*, "accès réseau type lecteur"). Sabre\DAV
 * traite normalement une requête HTTP en écrivant directement la réponse (header()/echo) — on
 * neutralise cet envoi (SilentSapi) et on construit nous-même la réponse Laravel à partir de
 * l'objet Sabre\HTTP\Response, pour rester dans le cycle de requête normal du framework (pas de
 * conflit d'en-têtes, testable comme n'importe quelle route).
 */
class WebDavController extends Controller
{
    public function __invoke(Request $request, AuthBackend $authBackend, WebDavContext $context)
    {
        $root = new FolderCollection($context, null, 'YOSEFA');

        $server = new Server($root, new SilentSapi());
        $server->setBaseUri('/webdav/');
        $server->addPlugin(new AuthPlugin($authBackend));

        // Remplace la requête (normalement lue depuis les superglobales PHP par Sabre) par une
        // reconstruction fidèle de la requête Laravel déjà parsée : évite de relire php://input,
        // potentiellement déjà consommé, et rend le contrôleur testable comme une route normale.
        $server->httpRequest = new SabreRequest($request->method(), $request->getRequestUri(), $request->headers->all(), $request->getContent());
        $server->httpResponse = new SabreResponse();

        // start() (alias exec()) convertit lui-même les exceptions Sabre (401, 403, 404...) en
        // réponse HTTP correcte ; invokeMethod() seul ne le fait pas.
        $server->start();

        $sabreResponse = $server->httpResponse;
        $body = $sabreResponse->getBody();

        if (is_resource($body)) {
            return response()->stream(
                function () use ($body): void {
                    fpassthru($body);
                },
                $sabreResponse->getStatus(),
                $sabreResponse->getHeaders(),
            );
        }

        return new LaravelResponse($sabreResponse->getBodyAsString(), $sabreResponse->getStatus(), $sabreResponse->getHeaders());
    }
}
