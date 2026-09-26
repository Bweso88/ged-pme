<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithTenants;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use InteractsWithTenants, RefreshDatabase;

    public function test_login_with_valid_credentials_returns_a_usable_token(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        // actingAsCompanyUser() authentifie la session web (nécessaire pour créer les
        // fixtures sous le bon tenant) : le guard sanctum, s'il trouve déjà un utilisateur
        // authentifié sur le guard "web", le réutilise via un TransientToken et ignore le
        // vrai jeton Bearer. On force l'oubli des guards pour que ce test vérifie
        // effectivement le jeton renvoyé par /auth/login, pas la session laissée derrière.
        $this->app['auth']->forgetGuards();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'iPhone de test',
        ]);

        $response->assertOk()->assertJsonStructure(['data' => ['token', 'user' => ['id', 'nom', 'email']]]);

        $token = $response->json('data.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/tasks')
            ->assertOk();
    }

    public function test_login_with_wrong_password_is_rejected(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'mauvais-mot-de-passe',
            'device_name' => 'iPhone de test',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_login_locks_the_account_after_five_failed_attempts(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'mauvais-mot-de-passe',
                'device_name' => 'iPhone de test',
            ])->assertUnprocessable();
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'iPhone de test',
        ]);

        $response->assertUnprocessable();
        $this->assertStringContainsString('verrouillé', $response->json('errors.email.0'));
    }

    public function test_login_rejects_a_suspended_account(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $user->forceFill(['statut' => 'suspendu'])->save();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'iPhone de test',
        ]);

        $response->assertUnprocessable();
        $this->assertStringContainsString('suspendu', $response->json('errors.email.0'));
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $this->seedCatalog();
        $company = $this->createCompany();
        $user = $this->actingAsCompanyUser($company, Role::EMPLOYE);
        $this->app['auth']->forgetGuards();

        $token = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
            'device_name' => 'iPhone de test',
        ])->json('data.token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/auth/logout')
            ->assertNoContent();

        // Le guard sanctum met en cache l'utilisateur résolu pour toute la durée du test
        // (RequestGuard::user() ne réévalue le callback qu'une fois) : sans ce forgetGuards(),
        // cette requête réutiliserait le résultat en cache de la requête précédente au lieu
        // de re-vérifier le jeton (désormais supprimé) sur cette nouvelle requête.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v1/tasks')
            ->assertUnauthorized();
    }
}
