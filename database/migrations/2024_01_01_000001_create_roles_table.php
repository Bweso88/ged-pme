<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // NULL = rôle système, partagé par toutes les entreprises (super_admin, admin_entreprise, ...).
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('nom');
            $table->string('code')->nullable(); // ex: super_admin, admin_entreprise, manager...
            $table->boolean('is_system')->default(false);
            $table->timestamps();

            $table->unique(['company_id', 'nom']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
