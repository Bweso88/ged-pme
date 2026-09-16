<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->unsignedInteger('numero_version');
            $table->string('storage_path');
            $table->unsignedBigInteger('taille_octets');
            $table->string('hash_sha256', 64);
            $table->string('mime_type');
            $table->text('texte_ocr')->nullable();
            $table->string('ocr_statut')->default('non_requis'); // non_requis, en_attente, termine, echec
            $table->foreignId('auteur_id')->constrained('users')->restrictOnDelete();
            $table->string('commentaire')->nullable();
            $table->timestamps();

            $table->unique(['document_id', 'numero_version']);
        });

        // Index plein texte (français) sur le contenu OCR extrait, utilisé par la recherche avancée.
        DB::statement(
            "CREATE INDEX document_versions_texte_ocr_fulltext_idx ON document_versions USING GIN (to_tsvector('french', coalesce(texte_ocr, '')))"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('document_versions');
    }
};
