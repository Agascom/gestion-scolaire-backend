<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Absences : élèves (classe_id) ou enseignants (enseignant_id), justifiées ou non.
     */
    public function up(): void
    {
        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->foreignId('classe_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('eleve_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('enseignant_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date_absence');
            $table->string('motif')->nullable();
            $table->boolean('justifiee')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('annee_academique_id');
            $table->index('eleve_id');
            $table->index('classe_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absences');
    }
};