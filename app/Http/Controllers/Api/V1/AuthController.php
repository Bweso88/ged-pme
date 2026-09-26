<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Authentification par token (voir docs/02-architecture-technique.md, §9.1) : un token par
 * appareil (mobile). Mêmes règles de verrouillage/suspension que la connexion web
 * (App\Livewire\Auth\Login), dupliquées ici car les deux mécanismes d'authentification
 * (session vs token) restent volontairement séparés.
 */
class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCK_MINUTES = 15;

    public function login(Request $request): Response
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if ($user !== null && $user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => ['Compte temporairement verrouillé après plusieurs échecs de connexion. Réessayez plus tard.'],
            ]);
        }

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            $this->registerFailedAttempt($user);

            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        if ($user->statut !== 'actif') {
            throw ValidationException::withMessages([
                'email' => ['Ce compte est suspendu.'],
            ]);
        }

        if ($user->company !== null) {
            app(TenantContext::class)->set($user->company);
        }

        $user->forceFill([
            'tentatives_echouees' => 0,
            'verrouille_jusqu_a' => null,
            'derniere_connexion_at' => now(),
        ])->save();

        app(AuditLogger::class)->log($user, AuditLog::CONNEXION);

        $token = $user->createToken($data['device_name'])->plainTextToken;

        return response([
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'nom' => $user->name,
                    'email' => $user->email,
                ],
            ],
        ], 200);
    }

    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response(null, 204);
    }

    private function registerFailedAttempt(?User $user): void
    {
        if ($user === null) {
            return;
        }

        $attempts = $user->tentatives_echouees + 1;
        $lockUntil = $attempts >= self::MAX_ATTEMPTS ? now()->addMinutes(self::LOCK_MINUTES) : null;

        $user->forceFill([
            'tentatives_echouees' => $lockUntil !== null ? 0 : $attempts,
            'verrouille_jusqu_a' => $lockUntil,
        ])->save();
    }
}
