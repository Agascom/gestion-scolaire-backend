<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mouvements de stock : entrée (achat), sortie (vente, perte, ajustement).
     */
    public function up(): void
    {
        Schema::create('mouvements_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('produit_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['entree', 'sortie', 'vente', 'achat'])->default('entree');
            $table->decimal('quantite', 10, 2);
            $table->decimal('prix_unitaire', 12, 2);
            $table->decimal('montant', 12, 2);
            $table->string('reference')->nullable();
            $table->date('date_mouvement');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('produit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mouvements_stock');
    }
};