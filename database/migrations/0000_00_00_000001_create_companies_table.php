<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->string('couleur_primaire')->nullable();
            $table->string('plan')->default('starter'); // starter, business, enterprise
            $table->string('statut')->default('actif'); // actif, suspendu, resilie
            $table->unsignedInteger('quota_stockage_mo')->default(10240);
            $table->jsonb('parametres')->default('{}');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
