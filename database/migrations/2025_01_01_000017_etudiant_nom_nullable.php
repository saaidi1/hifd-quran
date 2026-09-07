<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** الاسم الكامل (لاتيني) اختياري في النموذج : يُترك null عند عدم ملئه. */
    public function up(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->string('nom')->nullable()->change();
            $table->string('prenom')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->string('nom')->nullable(false)->change();
            $table->string('prenom')->nullable(false)->change();
        });
    }
};
