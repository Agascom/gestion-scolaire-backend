<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Notifications : destinataires (utilisateur et/ou école), canal (mail, sms, push).
     */
    public function up(): void
    {
        Schema::create('notifications_app', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // paiement_retard, bulletin_publie, note_publiee, absence, conseil...
            $table->string('titre');
            $table->text('message')->nullable();
            $table->enum('canal', ['mail', 'sms', 'push'])->default('push');
            $table->boolean('lue')->default(false);
            $table->timestamp('lue_le')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('school_id');
            $table->index('user_id');
            $table->index(['user_id', 'lue']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications_app');
    }
};