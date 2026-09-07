<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('etudiants', function (Blueprint $table) {   // الطلبة
            $table->id();
            $table->string('matricule', 20)->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('nom_ar')->nullable();
            $table->string('prenom_ar')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->enum('sexe', ['M', 'F'])->default('M');
            $table->string('cin', 30)->nullable();
            $table->string('photo')->nullable();
            $table->string('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->string('telephone', 30)->nullable();

            // ولي الأمر
            $table->string('tuteur_nom')->nullable();
            $table->string('tuteur_lien', 50)->nullable();
            $table->string('tuteur_telephone', 30)->nullable();

            $table->string('niveau_scolaire')->nullable();
            $table->decimal('hifd_initial_hizb', 5, 2)->default(0);  // ما يحفظه عند التسجيل

            // Workflow d'inscription
            $table->enum('statut', ['preinscrit', 'en_test', 'valide', 'refuse', 'ajourne', 'abandon'])
                  ->default('preinscrit');
            $table->foreignId('preinscrit_par')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_preinscription')->nullable();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_validation')->nullable();
            $table->text('motif_refus')->nullable();

            $table->foreignId('groupe_id')->nullable()->constrained('groupes')->nullOnDelete();
            $table->boolean('interne')->default(false);   // مقيم بالداخلية
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['statut', 'groupe_id']);
        });
    }

    public function down(): void { Schema::dropIfExists('etudiants'); }
};
