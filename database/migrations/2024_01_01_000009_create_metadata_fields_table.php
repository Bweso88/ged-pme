<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metadata_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            // NULL = champ global, applicable à tous les types de documents.
            $table->foreignUuid('document_type_id')->nullable()->constrained('document_types')->cascadeOnDelete();
            $table->string('code');
            $table->string('label');
            $table->string('type'); // texte, nombre, date, liste, booleen
            $table->jsonb('options')->nullable(); // valeurs possibles si type = liste
            $table->boolean('obligatoire')->default(false);
            $table->integer('ordre')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'document_type_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata_fields');
    }
};
