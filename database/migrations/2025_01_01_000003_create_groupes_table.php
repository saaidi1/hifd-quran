<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groupes', function (Blueprint $table) {   // الحلقات
            $table->id();
            $table->string('nom');
            $table->string('nom_ar')->nullable();
            $table->foreignId('professeur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('niveau')->nullable();         // مبتدئ / متوسط / متقدم
            $table->string('salle')->nullable();
            $table->string('horaire')->nullable();
            $table->unsignedSmallInteger('capacite')->default(20);
            $table->string('annee_scolaire', 9)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->index(['actif', 'professeur_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('groupes'); }
};
