<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sourates', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('numero')->unique();  // 1 -> 114
            $table->string('nom_ar');
            $table->string('nom_fr');
            $table->unsignedSmallInteger('nb_ayat');
            $table->unsignedSmallInteger('ayah_cumul');       // versets cumulés avant cette sourate
            $table->enum('type', ['makkiyya', 'madaniyya']);
            $table->unsignedTinyInteger('juz_debut')->nullable();
        });
    }

    public function down(): void { Schema::dropIfExists('sourates'); }
};
