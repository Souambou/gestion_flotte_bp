<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicule_id')->constrained('vehicules')->cascadeOnDelete();
            $table->date('date_controle'); // un controle par vehicule et par jour
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('kilometrage')->nullable();
            $table->unsignedTinyInteger('niveau_carburant')->nullable(); // en %
            $table->enum('etat_general', ['bon', 'moyen', 'mauvais'])->default('bon');
            $table->json('points')->nullable();     // { cle: {statut, commentaire} }
            $table->json('photos')->nullable();     // chemins des photos
            $table->text('anomalies')->nullable();
            $table->text('commentaire')->nullable();
            $table->longText('signature')->nullable(); // data URL de la signature
            $table->timestamp('completee_at')->nullable();
            $table->timestamps();

            $table->unique(['vehicule_id', 'date_controle']);
            $table->index('date_controle');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checklists');
    }
};
