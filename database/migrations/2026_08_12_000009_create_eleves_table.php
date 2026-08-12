<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Élèves : fiche d'identité et statut de scolarité.
     */
    public function up(): void
    {
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('matricule')->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->enum('sexe', ['M', 'F']);
            $table->date('date_naissance');
            $table->string('commune_naissance')->nullable();
            $table->string('nationalite')->default('Gabonaise');
            $table->string('adresse')->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('statut', ['inscrit', 'reinscrit', 'transfere', 'radie', 'diplome'])->default('inscrit');
            $table->softDeletes();
            $table->timestamps();

            $table->index('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eleves');
    }
};