<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ressource_id était typé uuid, mais l'AuditLogger trace aussi des actions sur des
     * ressources à clé numérique (User, Role — voir doc 02, déviation pragmatique documentée).
     * Toute action sur un utilisateur (ex. création dans l'admin) faisait donc échouer
     * l'écriture du journal d'audit ("invalid input syntax for type uuid"). string(255) accepte
     * aussi bien un UUID qu'un identifiant numérique.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE audit_logs ALTER COLUMN ressource_id TYPE varchar(255)');
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE audit_logs ALTER COLUMN ressource_id TYPE uuid USING ressource_id::uuid');
    }
};
