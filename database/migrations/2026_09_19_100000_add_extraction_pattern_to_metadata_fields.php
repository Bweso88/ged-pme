<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expression régulière (1 groupe de capture) utilisée pour proposer une valeur à partir du
     * texte OCR d'un document — jamais pour la remplir automatiquement sans validation humaine
     * (voir App\Domain\Documents\Services\MetadataExtractionService).
     */
    public function up(): void
    {
        Schema::table('metadata_fields', function (Blueprint $table) {
            $table->text('extraction_pattern')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('metadata_fields', function (Blueprint $table) {
            $table->dropColumn('extraction_pattern');
        });
    }
};
