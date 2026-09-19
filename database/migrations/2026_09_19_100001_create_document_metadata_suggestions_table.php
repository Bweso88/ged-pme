<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Propositions d'extraction automatique (numéro de facture, montant, date...) issues du
     * texte OCR. Toujours à l'état "en_attente" tant qu'un humain ne les a pas acceptées :
     * jamais d'écriture directe dans document_metadata_values (voir doc 04, principe de
     * validation humaine explicite déjà appliqué à l'archivage et repris ici par cohérence).
     */
    public function up(): void
    {
        Schema::create('document_metadata_suggestions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignUuid('metadata_field_id')->constrained('metadata_fields')->cascadeOnDelete();
            $table->text('valeur_proposee');
            $table->string('statut')->default('en_attente'); // en_attente, acceptee, rejetee
            $table->timestamps();

            $table->unique(['document_id', 'metadata_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_metadata_suggestions');
    }
};
