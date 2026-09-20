<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * workflow_instances.etape_courante_id était en nullOnDelete : la base acceptait donc
     * silencieusement de supprimer l'étape sur laquelle un document est activement en attente,
     * laissant l'instance orpheline (voir App\Livewire\Admin\Workflows\Index::removeStep, qui
     * a un contrôle applicatif équivalent mais ne doit pas être la seule ligne de défense —
     * même principe que la Row-Level Security pour l'isolation tenant : la contrainte de
     * base est la garantie qui ne peut pas être contournée par un bug applicatif).
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE workflow_instances DROP CONSTRAINT workflow_instances_etape_courante_id_foreign');
        DB::statement(<<<'SQL'
            ALTER TABLE workflow_instances
            ADD CONSTRAINT workflow_instances_etape_courante_id_foreign
            FOREIGN KEY (etape_courante_id) REFERENCES workflow_steps(id) ON DELETE RESTRICT
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE workflow_instances DROP CONSTRAINT workflow_instances_etape_courante_id_foreign');
        DB::statement(<<<'SQL'
            ALTER TABLE workflow_instances
            ADD CONSTRAINT workflow_instances_etape_courante_id_foreign
            FOREIGN KEY (etape_courante_id) REFERENCES workflow_steps(id) ON DELETE SET NULL
        SQL);
    }
};
