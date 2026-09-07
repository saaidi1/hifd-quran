<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('prenom')->nullable()->after('name');
            $table->string('nom')->nullable()->after('prenom');
            $table->string('nom_ar')->nullable()->after('nom');
            $table->enum('role', ['directeur', 'superviseur', 'garde_general', 'professeur'])
                  ->default('professeur')->after('email');
            $table->string('telephone', 30)->nullable();
            $table->string('specialite')->nullable();   // تخصص الأستاذ (رواية ورش، حفص...)
            $table->boolean('actif')->default(true);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['prenom', 'nom', 'nom_ar', 'role', 'telephone', 'specialite', 'actif']);
        });
    }
};
