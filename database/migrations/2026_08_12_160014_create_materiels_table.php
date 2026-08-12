<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inventaire du matériel : mobiliers, équipements, livres, fournitures.
     * suivi : entrées/sorties, panne/maintenance, amortissement simplifié.
     */
    public function up(): void
    {
        Schema::create('materiels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->enum('categorie', ['salle', 'mobilier', 'equipement_info', 'livre', 'fourniture'])->default('mobilier');
            $table->string('libelle');
            $table->string('reference')->nullable();
            $table->string('etat')->default('bon'); // bon, usage, panne, maintenance
            $table->decimal('valeur', 12, 2)->default(0);
            $table->string('emplacement')->nullable();
            $table->date('date_acquisition')->nullable();
            $table->integer('duree_amortissement_mois')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('categorie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiels');
    }
};