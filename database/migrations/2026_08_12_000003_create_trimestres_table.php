<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trimestres (par défaut 3) rattachés à une année académique.
     */
    public function up(): void
    {
        Schema::create('trimestres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annee_academique_id')->constrained('annees_academiques')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->string('libelle');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('cloture')->default(false);
            $table->timestamps();

            $table->index('annee_academique_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trimestres');
    }
};