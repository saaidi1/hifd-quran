<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comportements', function (Blueprint $table) {  // السلوك والملاحظات
            $table->id();
            $table->foreignId('etudiant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('signale_par')->constrained('users');
            $table->date('date');
            $table->enum('type', ['positif', 'negatif']);
            $table->string('categorie')->nullable();   // نظافة، احترام، تأخير، شجار...
            $table->unsignedTinyInteger('gravite')->default(1);   // 1..3
            $table->smallInteger('points')->default(0);           // +/- points de conduite
            $table->text('description');
            $table->text('sanction')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_traitement')->nullable();
            $table->timestamps();
            $table->index(['etudiant_id', 'date']);
        });
    }

    public function down(): void { Schema::dropIfExists('comportements'); }
};
