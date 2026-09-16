<?php

declare(strict_types=1);

namespace Tests;

use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        // L'ordre compte : RefreshDatabase (parent::tearDown()) doit d'abord annuler la
        // transaction de test. Si un test a levé une exception SQL réelle, la transaction
        // reste "aborted" jusqu'au ROLLBACK — exécuter le SET du GUC tenant avant coupe la
        // connexion en pleine transaction avortée et bloque durablement les tests suivants.
        parent::tearDown();

        if ($this->app !== null) {
            $this->app->make(TenantContext::class)->clear();
        }
    }
}
