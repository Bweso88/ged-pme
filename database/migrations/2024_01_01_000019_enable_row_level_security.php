<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Défense en profondeur PostgreSQL (voir doc ged-pme/docs/02-architecture-technique.md, §6.2) :
     * même en cas d'oubli du Global Scope Eloquent côté application, la base refuse de
     * renvoyer/écrire les lignes d'un autre tenant. Le tenant courant est positionné par
     * App\Support\Tenancy\ResolveTenant (SET app.current_company_id = '<uuid>').
     *
     * Volontairement exclues : `companies` (table du Super Admin), `users`/`roles`/`permissions`
     * (lookup pré-authentification par email, catalogue global) — protégées par le Global Scope
     * et la revue de code applicative, pas par RLS.
     */
    private array $tenantTables = [
        'services',
        'folders',
        'document_types',
        'metadata_fields',
        'documents',
        'document_versions',
        'document_metadata_values',
        'document_shares',
        'workflow_definitions',
        'workflow_steps',
        'workflow_instances',
        'workflow_actions',
        'retention_policies',
        'archive_records',
        'audit_logs',
        'resource_permissions',
        'user_groups',
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tenantTables as $table) {
            DB::statement("ALTER TABLE {$table} ENABLE ROW LEVEL SECURITY");
            DB::statement("ALTER TABLE {$table} FORCE ROW LEVEL SECURITY");
            DB::statement(<<<SQL
                CREATE POLICY tenant_isolation ON {$table}
                USING (company_id = current_setting('app.current_company_id', true)::uuid)
                WITH CHECK (company_id = current_setting('app.current_company_id', true)::uuid)
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->tenantTables as $table) {
            DB::statement("DROP POLICY IF EXISTS tenant_isolation ON {$table}");
            DB::statement("ALTER TABLE {$table} DISABLE ROW LEVEL SECURITY");
        }
    }
};
