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
        Schema::create('document_shares', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('cree_par')->constrained('users')->restrictOnDelete();
            $table->string('type'); // utilisateur, groupe, lien
            $table->foreignId('cible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('cible_group_id')->nullable()->constrained('user_groups')->nullOnDelete();
            $table->string('token')->nullable()->unique();
            $table->string('mot_de_passe_hash')->nullable();
            $table->timestamp('expire_at')->nullable();
            $table->boolean('revoque')->default(false);
            $table->timestamps();
        });

        // Aucun lien public permanent : un partage de type "lien" doit obligatoirement expirer.
        DB::statement(
            "ALTER TABLE document_shares ADD CONSTRAINT chk_document_shares_lien_expire CHECK (type <> 'lien' OR expire_at IS NOT NULL)"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};
