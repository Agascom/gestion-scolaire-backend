<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bulletins : un bulletin par (élève, trimestre) ou bulletin annuel (trimestre_id null).
     */
    public function up(): void
    {
        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->foreignId('trimestre_id')->nullable()->constrained('trimestres')->nullOnDelete();
            $table->foreignId('eleve_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained()->cascadeOnDelete();
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->integer('rang')->nullable();
            $table->string('mention')->nullable();
            $table->string('appreciation')->nullable();
            $table->string('pdf_path')->nullable();
            $table->enum('statut', ['brouillon', 'publie'])->default('brouillon');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['eleve_id', 'trimestre_id', 'annee_academique_id']);
            $table->index('school_id');
            $table->index('classe_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulletins');
    }
};