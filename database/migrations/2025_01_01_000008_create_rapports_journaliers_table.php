<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapports_journaliers', function (Blueprint $table) {  // التقرير اليومي
            $table->id();
            $table->foreignId('etudiant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('professeur_id')->constrained('users');
            $table->foreignId('groupe_id')->constrained();
            $table->date('date');
            $table->enum('presence', ['present', 'absent', 'retard', 'excuse'])->default('present');
            $table->time('heure_arrivee')->nullable();
            $table->decimal('note_comportement', 5, 2)->nullable();
            $table->decimal('note_globale', 5, 2)->nullable();
            $table->text('remarques')->nullable();          // ملاحظات
            $table->boolean('verrouille')->default(false);  // figé après validation
            $table->timestamps();

            $table->unique(['etudiant_id', 'date']);        // un seul rapport par jour
            $table->index(['groupe_id', 'date']);
        });
    }

    public function down(): void { Schema::dropIfExists('rapports_journaliers'); }
};
