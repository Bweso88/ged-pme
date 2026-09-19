<?php

use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['tenant' => ResolveTenant::class]);
        $middleware->appendToGroup('web', ResolveTenant::class);
        $middleware->appendToGroup('api', ResolveTenant::class);

        // Doit s'exécuter après l'authentification (pour connaître l'utilisateur) mais avant
        // la résolution des bindings de route (Folder/Document) : sinon la Row-Level Security
        // PostgreSQL masque les ressources avant même que le tenant courant ne soit connu.
        $middleware->prependToPriorityList(
            before: SubstituteBindings::class,
            prepend: ResolveTenant::class,
        );

        // Le WebDAV s'authentifie lui-même (Basic Auth, voir App\Domain\WebDav\AuthBackend) :
        // un client réseau (Windows/Mac) n'envoie pas de jeton CSRF sur ses requêtes PUT/DELETE.
        $middleware->validateCsrfTokens(except: ['webdav/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
