<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taches_memorisation', function (Blueprint $table) {  // الواجب المقرر
            $table->id();
            $table->foreignId('etudiant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professeur_id')->constrained('users');
            $table->enum('type', ['hifd_jadid', 'murajaa_qariba', 'murajaa_baida', 'tilawa']);

            $table->foreignId('sourate_debut_id')->constrained('sourates');
            $table->unsignedSmallInteger('ayah_debut');
            $table->foreignId('sourate_fin_id')->constrained('sourates');
            $table->unsignedSmallInteger('ayah_fin');
            $table->decimal('nb_pages', 5, 2)->nullable();     // عدد الأوجه

            $table->date('date_assignation');
            $table->date('date_echeance');
            $table->text('consignes')->nullable();
            $table->enum('statut', ['assignee', 'realisee', 'partielle', 'non_realisee'])->default('assignee');
            $table->timestamps();

            $table->index(['etudiant_id', 'date_echeance']);
        });
    }

    public function down(): void { Schema::dropIfExists('taches_memorisation'); }
};
