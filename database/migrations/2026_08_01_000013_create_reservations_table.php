<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // le commercial demandeur
            $table->foreignId('vehicule_id')->nullable()->constrained('vehicules')->nullOnDelete();
            $table->foreignId('chauffeur_id')->nullable()->constrained('chauffeurs')->nullOnDelete();
            $table->boolean('avec_chauffeur')->default(true);
            $table->enum('type_deplacement', ['sortie_simple', 'mission'])->default('sortie_simple');
            $table->string('departement', 60); // service demandeur, cf. config beninpetro.departements
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->string('lieu_depart');
            $table->string('lieu_arrivee');
            $table->decimal('depart_latitude', 10, 7)->nullable();
            $table->decimal('depart_longitude', 10, 7)->nullable();
            $table->decimal('arrivee_latitude', 10, 7)->nullable();
            $table->decimal('arrivee_longitude', 10, 7)->nullable();
            $table->unsignedInteger('distance_estimee_km')->nullable();
            $table->unsignedInteger('duree_estimee_min')->nullable();
            $table->text('motif');
            $table->enum('statut', ['en_attente', 'validee', 'refusee', 'en_cours', 'terminee', 'annulee'])->default('en_attente');
            $table->text('motif_refus')->nullable();
            $table->text('alternative_proposee')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traite_at')->nullable();
            $table->foreignId('annule_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('annule_at')->nullable();
            $table->text('motif_annulation')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['statut', 'date_debut']);
            $table->index(['departement', 'created_at']);
            $table->index(['vehicule_id', 'date_debut', 'date_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
