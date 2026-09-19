<?php

declare(strict_types=1);

namespace App\Domain\Security;

use RuntimeException;

/**
 * Levée quand le moteur antivirus n'a pas pu rendre de verdict (démon injoignable, erreur
 * d'exécution...). L'appelant doit traiter ce cas comme un échec fermé (fail closed) :
 * un fichier non vérifiable ne doit jamais être accepté silencieusement.
 */
class AntivirusScanFailedException extends RuntimeException {}
