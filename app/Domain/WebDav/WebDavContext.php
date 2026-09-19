<?php

declare(strict_types=1);

namespace App\Domain\WebDav;

use App\Models\User;
use RuntimeException;

/**
 * Porte l'utilisateur authentifié par Sabre (Basic Auth, indépendant de la session Laravel)
 * pendant la durée d'une requête WebDAV, pour que les nœuds de l'arborescence (construits avant
 * que l'authentification n'ait lieu) puissent y accéder au moment où Sabre les interroge.
 * Enregistré en singleton (voir AppServiceProvider).
 */
class WebDavContext
{
    private ?User $user = null;

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function user(): User
    {
        return $this->user ?? throw new RuntimeException('Contexte WebDAV non authentifié.');
    }
}
