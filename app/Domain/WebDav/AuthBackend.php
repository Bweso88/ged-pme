<?php

declare(strict_types=1);

namespace App\Domain\WebDav;

use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Hash;
use Sabre\DAV\Auth\Backend\AbstractBasic;

/**
 * Authentification WebDAV (Basic Auth, indépendante de la session/cookie Laravel — les clients
 * WebDAV du système d'exploitation ne portent pas de cookie de session). Identifiants = ceux du
 * compte YOSEFA habituel (email + mot de passe). Positionne le contexte tenant comme le ferait
 * App\Http\Middleware\ResolveTenant pour une requête HTTP classique authentifiée.
 */
class AuthBackend extends AbstractBasic
{
    protected $realm = 'YOSEFA';

    public function __construct(
        private readonly WebDavContext $context,
        private readonly TenantContext $tenantContext,
    ) {}

    protected function validateUserPass($username, $password): bool
    {
        $user = User::query()->where('email', $username)->first();

        if ($user === null || $user->statut !== 'actif' || ! Hash::check($password, $user->password)) {
            return false;
        }

        $this->context->setUser($user);
        $this->tenantContext->set($user->company);

        return true;
    }
}
