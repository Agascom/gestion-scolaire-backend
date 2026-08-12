<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lie un compte utilisateur (role parent) aux fiches parents (1 parent = plusieurs enfants possibles).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['user_id', 'eleve_id']);
        });
    }

    public function down(): void
    {
        Schema::table('parents', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'eleve_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};