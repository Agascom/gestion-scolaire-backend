<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Frais de scolarité : inscription, scolarité, transport, tenues...
     * Peuvent être rattachés à un cycle et/ou une classe, avec périodicité.
     */
    public function up(): void
    {
        Schema::create('frais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('libelle');
            $table->decimal('montant', 12, 2);
            $table->enum('periodicite', ['annee', 'trimestre', 'mensuel'])->default('annee');
            $table->foreignId('cycle_id')->nullable()->constrained('cycles')->nullOnDelete();
            $table->foreignId('classe_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('cycle_id');
            $table->index('classe_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frais');
    }
};