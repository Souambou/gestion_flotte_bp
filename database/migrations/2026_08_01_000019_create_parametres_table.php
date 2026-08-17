<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres', function (Blueprint $table) {
            $table->id();
            $table->string('cle')->unique();
            $table->text('valeur')->nullable();
            $table->string('groupe')->default('general'); // general | integrations | notifications | reservation
            $table->string('libelle');
            $table->string('type')->default('text');      // text | password | number | boolean | textarea | select
            $table->text('description')->nullable();
            $table->boolean('chiffre')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres');
    }
};
