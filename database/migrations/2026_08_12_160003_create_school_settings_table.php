<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paramètres de l'école : seuils, coefficients, TVA (18% Gabon par défaut), taux de passage, etc.
     * Structure clé / valeur, configurable par école.
     */
    public function up(): void
    {
        Schema::create('school_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('cle');
            $table->text('valeur')->nullable();
            $table->timestamps();

            $table->unique(['school_id', 'cle']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_settings');
    }
};