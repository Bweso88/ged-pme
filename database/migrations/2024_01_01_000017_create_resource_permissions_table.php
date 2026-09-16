<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Octroi/retrait d'une permission à un niveau plus fin qu'un rôle : un dossier ou un
     * document précis, pour un utilisateur ou un groupe. Surcharge l'héritage du rôle
     * uniquement sur le périmètre concerné (voir doc 04, section 12).
     */
    public function up(): void
    {
        Schema::create('resource_permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('user_group_id')->nullable()->constrained('user_groups')->cascadeOnDelete();
            $table->string('resource_type'); // folder, document
            $table->uuid('resource_id');
            $table->string('permission_code');
            $table->boolean('granted')->default(true); // false = refus explicite (prioritaire)
            $table->timestamps();

            $table->index(['company_id', 'resource_type', 'resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resource_permissions');
    }
};
