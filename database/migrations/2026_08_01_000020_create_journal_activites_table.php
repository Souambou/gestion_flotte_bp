<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_activites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('sujet_type')->nullable();
            $table->unsignedBigInteger('sujet_id')->nullable();
            $table->text('description')->nullable();
            $table->json('donnees')->nullable();
            $table->string('adresse_ip', 45)->nullable();
            $table->timestamps();

            $table->index(['sujet_type', 'sujet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_activites');
    }
};
