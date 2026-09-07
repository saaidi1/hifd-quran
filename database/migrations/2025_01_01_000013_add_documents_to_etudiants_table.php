<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->string('extrait_naissance')->nullable()->after('photo');
            $table->string('attestation_scolaire')->nullable()->after('extrait_naissance');
            $table->string('autre_document')->nullable()->after('attestation_scolaire');
        });
    }

    public function down(): void
    {
        Schema::table('etudiants', function (Blueprint $table) {
            $table->dropColumn(['extrait_naissance', 'attestation_scolaire', 'autre_document']);
        });
    }
};
