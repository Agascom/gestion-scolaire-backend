<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dépenses de l'école : nature (achat, maintenance, facture, fourniture), fournisseur, pièce jointe.
     */
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->enum('nature', ['achat', 'maintenance', 'facture', 'fourniture'])->default('achat');
            $table->string('libelle');
            $table->string('fournisseur')->nullable();
            $table->decimal('montant', 12, 2);
            $table->date('date_depense');
            $table->string('piece_jointe_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('annee_academique_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};