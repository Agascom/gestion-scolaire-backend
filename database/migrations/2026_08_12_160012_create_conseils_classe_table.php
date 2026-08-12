<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conseils de classe : trimestre de référence, décisions, mentions.
     */
    public function up(): void
    {
        Schema::create('conseils_classe', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->foreignId('trimestre_id')->nullable()->constrained('trimestres')->nullOnDelete();
            $table->foreignId('classe_id')->constrained()->cascadeOnDelete();
            $table->date('date_conseil');
            $table->text('decisions')->nullable();
            $table->boolean('valide')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index(['trimestre_id', 'classe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conseils_classe');
    }
};