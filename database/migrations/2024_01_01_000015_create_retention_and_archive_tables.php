<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('document_type_id')->constrained('document_types')->cascadeOnDelete();
            $table->unsignedInteger('duree_conservation_mois');
            // conserver, proposer_destruction, transferer_archive, demander_validation
            $table->string('action_a_expiration');
            $table->string('categorie_archive')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'document_type_id']);
        });

        Schema::create('archive_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->timestamp('date_archivage');
            $table->string('categorie')->nullable();
            $table->string('confidentialite');
            $table->string('motif')->nullable();
            $table->date('date_destruction_prevue')->nullable();
            // actif, propose_destruction, valide_destruction, detruit
            $table->string('statut')->default('actif');
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_records');
        Schema::dropIfExists('retention_policies');
    }
};
