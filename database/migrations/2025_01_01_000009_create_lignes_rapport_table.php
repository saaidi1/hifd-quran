<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_rapport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rapport_id')->constrained('rapports_journaliers')->cascadeOnDelete();
            $table->foreignId('tache_id')->nullable()->constrained('taches_memorisation')->nullOnDelete();
            $table->enum('type', ['hifd_jadid', 'murajaa_qariba', 'murajaa_baida', 'tilawa']);

            $table->foreignId('sourate_debut_id')->constrained('sourates');
            $table->unsignedSmallInteger('ayah_debut');
            $table->foreignId('sourate_fin_id')->constrained('sourates');
            $table->unsignedSmallInteger('ayah_fin');
            $table->decimal('nb_pages', 5, 2)->nullable();

            $table->decimal('note', 5, 2)->nullable();               // /20
            $table->unsignedSmallInteger('nb_erreurs')->default(0);      // الأخطاء
            $table->unsignedSmallInteger('nb_hesitations')->default(0);  // التنبيهات / الفتح
            $table->enum('maitrise', ['excellent', 'tres_bien', 'bien', 'moyen', 'faible'])->nullable();
            $table->text('observation')->nullable();
            $table->timestamps();

            $table->index(['rapport_id', 'type']);
        });
    }

    public function down(): void { Schema::dropIfExists('lignes_rapport'); }
};
