<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Affectation Matière ↔ Classe (+ Enseignant) avec coefficient, par école et par année.
     */
    public function up(): void
    {
        Schema::create('matiere_classe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enseignant_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('coefficient', 5, 2)->default(1);
            $table->timestamps();

            $table->unique(['classe_id', 'matiere_id']);
            $table->index('school_id');
            $table->index('matiere_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matiere_classe');
    }
};