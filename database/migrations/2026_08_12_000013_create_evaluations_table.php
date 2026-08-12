<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Évaluations (interrogation, devoir, composition, examen) par trimestre et par classe/matière.
     */
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('matiere_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trimestre_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['interrogation', 'devoir', 'composition', 'examen']);
            $table->string('libelle');
            $table->date('date_evaluation');
            $table->decimal('note_sur', 5, 2)->default(20);
            $table->boolean('publiee')->default(false);
            $table->timestamps();

            $table->unique(['classe_id', 'matiere_id', 'trimestre_id', 'type', 'libelle']);
            $table->index('school_id');
            $table->index('matiere_id');
            $table->index('trimestre_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};