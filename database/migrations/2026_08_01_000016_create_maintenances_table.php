<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicule_id')->constrained('vehicules')->cascadeOnDelete();
            $table->enum('type', ['preventive', 'corrective', 'revision', 'reparation', 'controle_technique']);
            $table->string('intitule');
            $table->text('description')->nullable();
            $table->date('date_prevue')->nullable();
            $table->date('date_realisee')->nullable();
            $table->unsignedBigInteger('kilometrage')->nullable();
            $table->decimal('cout', 12, 2)->nullable();
            $table->string('prestataire')->nullable();
            $table->enum('statut', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
            $table->foreignId('cree_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
