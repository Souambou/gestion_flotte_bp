<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deplacements', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('vehicule_id')->constrained('vehicules')->cascadeOnDelete();
            $table->foreignId('chauffeur_id')->nullable()->constrained('chauffeurs')->nullOnDelete();
            $table->dateTime('depart_reel_at')->nullable();
            $table->dateTime('arrivee_reelle_at')->nullable();
            $table->unsignedBigInteger('km_depart')->nullable();
            $table->unsignedBigInteger('km_arrivee')->nullable();
            $table->decimal('carburant_consomme', 8, 2)->nullable();
            $table->decimal('cout_carburant', 12, 2)->nullable();
            $table->decimal('autres_frais', 12, 2)->nullable();
            $table->enum('statut', ['planifiee', 'en_cours', 'terminee', 'incident'])->default('planifiee');
            $table->text('observations')->nullable();
            $table->foreignId('ouverte_par')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cloturee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['statut', 'depart_reel_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deplacements');
    }
};
