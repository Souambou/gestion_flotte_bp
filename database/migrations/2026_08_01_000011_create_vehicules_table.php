<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicules', function (Blueprint $table) {
            $table->id();
            $table->string('immatriculation', 30)->unique();
            $table->string('marque');
            $table->string('modele');
            $table->enum('type', ['berline', 'suv', 'pickup', 'camion_citerne', 'utilitaire', 'minibus', 'moto'])->default('berline');
            $table->year('annee')->nullable();
            $table->enum('carburant', ['essence', 'gasoil', 'hybride', 'electrique'])->default('essence');
            $table->unsignedTinyInteger('nombre_places')->default(5);
            $table->unsignedBigInteger('kilometrage')->default(0);
            $table->enum('statut', ['disponible', 'occupe', 'en_deplacement', 'en_maintenance', 'hors_service'])->default('disponible');
            $table->foreignId('agence_id')->nullable()->constrained('agences')->nullOnDelete();
            $table->string('photo')->nullable();
            $table->date('date_mise_en_service')->nullable();
            $table->date('date_expiration_assurance')->nullable();
            $table->date('date_visite_technique')->nullable();
            $table->date('date_prochaine_maintenance')->nullable();
            $table->unsignedBigInteger('km_prochaine_maintenance')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('position_maj_at')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['statut', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicules');
    }
};
