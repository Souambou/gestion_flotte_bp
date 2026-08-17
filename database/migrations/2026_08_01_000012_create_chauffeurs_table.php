<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chauffeurs', function (Blueprint $table) {
            $table->id();
            $table->string('matricule', 30)->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone', 30);
            $table->string('email')->nullable();
            $table->string('numero_permis', 50);
            $table->string('categorie_permis', 20)->default('B');
            $table->date('date_expiration_permis')->nullable();
            $table->date('date_embauche')->nullable();
            $table->enum('statut', ['disponible', 'en_deplacement', 'indisponible', 'conge'])->default('disponible');
            $table->foreignId('agence_id')->nullable()->constrained('agences')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('photo')->nullable();
            $table->text('observations')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chauffeurs');
    }
};
