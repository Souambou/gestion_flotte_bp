<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('litiges', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('reservation_id')->nullable()->constrained('reservations')->nullOnDelete();
            $table->foreignId('vehicule_id')->nullable()->constrained('vehicules')->nullOnDelete();
            $table->foreignId('declare_par')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['annulation', 'retard', 'dommage', 'facturation', 'comportement', 'autre'])->default('autre');
            $table->string('objet');
            $table->text('description');
            $table->enum('gravite', ['faible', 'moyenne', 'elevee'])->default('moyenne');
            $table->enum('statut', ['ouvert', 'en_traitement', 'resolu', 'clos'])->default('ouvert');
            $table->text('resolution')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolu_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('litiges');
    }
};
