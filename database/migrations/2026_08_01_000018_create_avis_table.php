<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('note'); // 1 a 5
            $table->unsignedTinyInteger('note_vehicule')->nullable();
            $table->unsignedTinyInteger('note_chauffeur')->nullable();
            $table->text('commentaire')->nullable();
            $table->boolean('publie')->default(true);
            $table->timestamps();

            $table->unique('reservation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis');
    }
};
