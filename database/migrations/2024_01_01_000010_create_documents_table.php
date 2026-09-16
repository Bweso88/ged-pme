<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('folder_id')->constrained('folders')->restrictOnDelete();
            $table->foreignUuid('document_type_id')->nullable()->constrained('document_types')->nullOnDelete();
            $table->string('nom');
            $table->string('reference')->nullable();
            $table->string('statut')->default('brouillon'); // brouillon, soumis, en_validation, publie, archive
            $table->string('confidentialite')->default('interne'); // public_entreprise, restreint, confidentiel
            $table->foreignId('auteur_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('proprietaire_id')->constrained('users')->restrictOnDelete();
            $table->jsonb('mots_cles')->default('[]');
            $table->date('date_document')->nullable();
            $table->date('date_expiration')->nullable();
            // Pointeur applicatif vers la version courante (pas de contrainte FK : évite le cycle documents <-> document_versions).
            $table->uuid('version_courante_id')->nullable();
            $table->boolean('is_trashed')->default(false);
            $table->timestamp('trashed_at')->nullable();
            $table->foreignId('trashed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'folder_id']);
            $table->index(['company_id', 'statut']);
            $table->index(['company_id', 'is_trashed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
