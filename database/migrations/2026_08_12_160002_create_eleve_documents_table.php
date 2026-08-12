<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Documents attachés au dossier de l'élève (acte de naissance, carnet de santé,
     * certificat, relevés de notes...).
     */
    public function up(): void
    {
        Schema::create('eleve_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('eleve_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['acte_naissance', 'carnet_sante', 'certificat', 'releve'])->default('acte_naissance');
            $table->string('libelle')->nullable();
            $table->string('fichier_path');
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('school_id');
            $table->index('eleve_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eleve_documents');
    }
};