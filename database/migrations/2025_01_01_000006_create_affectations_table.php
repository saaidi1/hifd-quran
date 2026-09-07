<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('groupe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('affecte_par')->constrained('users');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->string('motif')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->index(['etudiant_id', 'actif']);
        });
    }

    public function down(): void { Schema::dropIfExists('affectations'); }
};
