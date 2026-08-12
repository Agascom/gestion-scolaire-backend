<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paie : salaire de base, primes, avances, retenues, net à payer par mois et par enseignant.
     */
    public function up(): void
    {
        Schema::create('salaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->foreignId('enseignant_id')->constrained()->cascadeOnDelete();
            $table->string('mois'); // ex : 2025-10
            $table->decimal('salaire_base', 12, 2)->default(0);
            $table->decimal('primes', 12, 2)->default(0);
            $table->decimal('avances', 12, 2)->default(0);
            $table->decimal('retenues', 12, 2)->default(0);
            $table->decimal('net_a_payer', 12, 2)->default(0);
            $table->enum('statut', ['paye', 'en_attente'])->default('en_attente');
            $table->date('date_paiement')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['enseignant_id', 'mois']);
            $table->index('school_id');
            $table->index('annee_academique_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salaires');
    }
};