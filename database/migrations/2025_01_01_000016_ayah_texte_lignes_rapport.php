<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lignes_rapport', function (Blueprint $table) {
            $table->string('ayah_debut', 50)->change();
            $table->string('ayah_fin', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('lignes_rapport', function (Blueprint $table) {
            $table->unsignedSmallInteger('ayah_debut')->change();
            $table->unsignedSmallInteger('ayah_fin')->change();
        });
    }
};
