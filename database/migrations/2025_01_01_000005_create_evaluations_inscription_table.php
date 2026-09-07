<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations_inscription', function (Blueprint $table) {
            $table->id();
            $table->foreignId('etudiant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('superviseur_id')->constrained('users');
            $table->date('date_test');
            $table->decimal('hizb_maitrise', 5, 2)->default(0);
            $table->decimal('note_hifd', 5, 2)->nullable();
            $table->decimal('note_tajwid', 5, 2)->nullable();
            $table->decimal('note_lecture', 5, 2)->nullable();
            $table->foreignId('sourate_testee_id')->nullable()->constrained('sourates');
            $table->text('observations')->nullable();
            $table->enum('decision', ['valide', 'refuse', 'ajourne']);
            $table->text('motif')->nullable();
            $table->string('niveau_propose')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('evaluations_inscription'); }
};
