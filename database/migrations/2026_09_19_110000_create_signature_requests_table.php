<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Traçabilité des demandes de signature électronique (voir doc 04, §13.3). Distinct de
     * "Approuver" en workflow, qui n'a pas de valeur juridique de signature — voir
     * App\Domain\Signature\SignatureProvider.
     */
    public function up(): void
    {
        Schema::create('signature_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('demande_par_id')->constrained('users');
            $table->string('provider'); // "yousign"
            $table->string('external_id')->nullable();
            $table->string('statut')->default('en_attente'); // en_attente, envoye, signe, refuse, expire, erreur
            $table->jsonb('signataires'); // [{"nom": "...", "email": "..."}, ...]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_requests');
    }
};
