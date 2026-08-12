<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Emploi du temps : créneau par classe/enseignant/matière/salle.
     * Anti-conflit : une seule contrainte par (enseignant, jour, heure) gérée en code (service).
     */
    public function up(): void
    {
        Schema::create('creneaux_edt', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enseignant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('matiere_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('salle_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('jour', ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi']);
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('classe_id');
            $table->index('enseignant_id');
            $table->index('salle_id');
            $table->index(['jour', 'heure_debut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creneaux_edt');
    }
};