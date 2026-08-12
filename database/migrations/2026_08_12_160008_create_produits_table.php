<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Produits (cantine, kits, tenues...) : stock géré par l'école marchande.
     */
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('libelle');
            $table->string('reference')->nullable();
            $table->decimal('prix_achat', 12, 2)->default(0);
            $table->decimal('prix_vente', 12, 2)->default(0);
            $table->decimal('quantite_stock', 10, 2)->default(0);
            $table->string('unite')->default('unite');
            $table->decimal('taux_tva', 5, 2)->default(18);
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};