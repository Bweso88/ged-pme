<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_metadata_values', function (Blueprint $table) {
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignUuid('metadata_field_id')->constrained('metadata_fields')->cascadeOnDelete();
            $table->text('valeur')->nullable();

            $table->primary(['document_id', 'metadata_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_metadata_values');
    }
};
