<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    private const MAX_ATTEMPTS = 5;

    private const LOCK_MINUTES = 15;

    public function submit(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $this->email)->first();

        if ($user !== null && $user->isLocked()) {
            throw ValidationException::withMessages([
                'email' => 'Compte temporairement verrouillé après plusieurs échecs de connexion. Réessayez plus tard.',
            ]);
        }

        if ($user === null || ! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->registerFailedAttempt($user);

            throw ValidationException::withMessages([
                'email' => 'Identifiants incorrects.',
            ]);
        }

        if ($user->statut !== 'actif') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Ce compte est suspendu.',
            ]);
        }

        session()->regenerate();

        // Ce même cycle de requête doit pouvoir écrire l'entrée d'audit "connexion" ; le
        // middleware ResolveTenant ne le fait qu'aux requêtes suivantes (l'utilisateur vient
        // de s'authentifier), donc on établit explicitement le contexte tenant ici.
        if ($user->company !== null) {
            app(TenantContext::class)->set($user->company);
        }

        $user->forceFill([
            'tentatives_echouees' => 0,
            'verrouille_jusqu_a' => null,
            'derniere_connexion_at' => now(),
        ])->save();

        app(AuditLogger::class)->log($user, AuditLog::CONNEXION);

        $this->redirect(route('dashboard'), navigate: true);
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

    public function render()
    {
        return view('livewire.auth.login');
    }
}
