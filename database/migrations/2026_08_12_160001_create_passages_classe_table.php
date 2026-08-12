<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historique des passages en classe supérieure (décision conseil de classe).
     */
    public function up(): void
    {
        Schema::create('passages_classe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->foreignId('classe_source_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('classe_cible_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->enum('decision', ['admis', 'redoublant', 'saut_classe', 'diplome'])->default('admis');
            $table->string('appreciation')->nullable();
            $table->foreignId('decide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['eleve_id', 'annee_academique_id']);
            $table->index('school_id');
            $table->index('classe_source_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passages_classe');
    }
};