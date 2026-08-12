<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Encaissements (paiements parents) : mode de paiement, référence, statut, reçu.
     * Un paiement partiel est un encaissement de montant < frais avec statut 'partiel'.
     */
    public function up(): void
    {
        Schema::create('encaissements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained()->cascadeOnDelete();
            $table->foreignId('frais_id')->nullable()->constrained('frais')->nullOnDelete();
            $table->decimal('montant', 12, 2);
            $table->enum('mode', ['especes', 'mobile_money', 'virement', 'cheque'])->default('especes');
            $table->string('reference')->nullable();
            $table->enum('statut', ['paye', 'partiel', 'en_attente'])->default('paye');
            $table->date('date_encaissement');
            $table->string('numero_recu')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('annee_academique_id');
            $table->index('eleve_id');
            $table->index('frais_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encaissements');
    }
};