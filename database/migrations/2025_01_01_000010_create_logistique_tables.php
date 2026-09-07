<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // الغرف
        Schema::create('chambres', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20);
            $table->string('batiment', 50)->nullable();
            $table->string('etage', 20)->nullable();
            $table->unsignedSmallInteger('capacite')->default(4);
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['batiment', 'numero']);
        });

        // الإيواء ورقم السرير
        Schema::create('hebergements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chambre_id')->constrained();
            $table->unsignedSmallInteger('numero_lit');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->foreignId('attribue_par')->nullable()->constrained('users')->nullOnDelete();
            $table->string('observation')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            // un lit ne peut être occupé que par un seul étudiant actif
            $table->unique(['chambre_id', 'numero_lit', 'actif'], 'uniq_lit_actif');
        });

        // مكان الصلاة
        Schema::create('lieux_priere', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('nom_ar')->nullable();
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('nb_rangees')->default(10);
            $table->unsignedSmallInteger('places_par_rangee')->default(20);
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('places_priere', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lieu_priere_id')->constrained('lieux_priere');
            $table->unsignedSmallInteger('rangee');        // الصف
            $table->unsignedSmallInteger('numero_place');  // رقم المكان
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->foreignId('attribue_par')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->unique(['lieu_priere_id', 'rangee', 'numero_place', 'actif'], 'uniq_place_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places_priere');
        Schema::dropIfExists('lieux_priere');
        Schema::dropIfExists('hebergements');
        Schema::dropIfExists('chambres');
    }
};
