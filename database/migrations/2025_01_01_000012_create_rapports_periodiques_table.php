<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports_periodiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('groupe_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['hebdomadaire', 'mensuel']);
            $table->date('date_debut');
            $table->date('date_fin');

            $table->unsignedSmallInteger('nb_seances')->default(0);
            $table->unsignedSmallInteger('nb_presences')->default(0);
            $table->unsignedSmallInteger('nb_absences')->default(0);
            $table->unsignedSmallInteger('nb_retards')->default(0);

            $table->decimal('total_pages_hifd', 7, 2)->default(0);
            $table->decimal('total_pages_murajaa', 7, 2)->default(0);
            $table->decimal('moyenne_hifd', 5, 2)->nullable();
            $table->decimal('moyenne_murajaa', 5, 2)->nullable();
            $table->decimal('moyenne_comportement', 5, 2)->nullable();
            $table->decimal('moyenne_generale', 5, 2)->nullable();

            $table->text('appreciation')->nullable();
            $table->foreignId('genere_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['etudiant_id', 'type', 'date_debut'], 'uniq_rapport_periode');
        });
    }

    public function down(): void { Schema::dropIfExists('rapports_periodiques'); }
};
