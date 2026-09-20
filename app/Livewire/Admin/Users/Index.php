<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Users;

use App\Domain\Audit\Services\AuditLogger;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Index extends Component
{
    public bool $showForm = false;

    public string $name = '';

    public string $email = '';

    public string $roleId = '';

    public ?int $generatedPasswordUserId = null;

    public ?string $generatedPassword = null;

    public function mount(): void
    {
        Gate::authorize('admin.users');
    }

    public function createUser(): void
    {
        Gate::authorize('admin.users');

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'roleId' => ['required', 'exists:roles,id'],
        ]);

        $company = Auth::user()->company;

        $user = User::query()->create([
            'company_id' => $company->id,
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make(str()->random(24)),
        ]);

        $user->roles()->attach($this->roleId);

        app(AuditLogger::class)->log(Auth::user(), AuditLog::CREATION, $user);

        $this->reset(['name', 'email', 'roleId', 'showForm']);
    }

    public function resetPassword(string $userId): void
    {
        Gate::authorize('admin.users');

        $user = User::query()->where('company_id', Auth::user()->company_id)->findOrFail($userId);
        $temporaryPassword = Str::password(12, symbols: false, spaces: false);

        $user->forceFill(['password' => Hash::make($temporaryPassword)])->save();

        app(AuditLogger::class)->log(Auth::user(), AuditLog::MODIFICATION, $user, ['action' => 'reinitialisation_mot_de_passe']);

        $this->generatedPasswordUserId = $user->id;
        $this->generatedPassword = $temporaryPassword;
    }

    public function toggleSuspend(string $userId): void
    {
        Gate::authorize('admin.users');

        $user = User::query()->where('company_id', Auth::user()->company_id)->findOrFail($userId);
        $user->forceFill(['statut' => $user->statut === 'actif' ? 'suspendu' : 'actif'])->save();

        app(AuditLogger::class)->log(Auth::user(), AuditLog::CHANGEMENT_PERMISSION, $user, ['nouveau_statut' => $user->statut]);
    }

    public function render()
    {
        $company = Auth::user()->company;

        return view('livewire.admin.users.index', [
            'users' => User::query()->where('company_id', $company->id)->with('roles')->orderBy('name')->get(),
            'roles' => Role::availableFor($company)->orderBy('nom')->get(),
        ]);
    }
}
