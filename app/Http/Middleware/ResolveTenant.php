<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Résout le tenant courant à partir de l'utilisateur authentifié (jamais depuis l'URL ou un
 * champ de formulaire) et le rend disponible à toute la requête via TenantContext, y compris
 * pour la synchronisation de la Row-Level Security PostgreSQL.
 */
class ResolveTenant
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->company_id !== null) {
            $this->tenantContext->set($user->company);
        }

        return $next($request);
    }
}
