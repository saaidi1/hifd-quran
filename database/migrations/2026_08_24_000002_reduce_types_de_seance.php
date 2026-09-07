<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Deux types de تسميع uniquement : حفظ جديد / حفظ قديم. */
return new class extends Migration
{
    private const ANCIENNES = ['murajaa_qariba', 'murajaa_baida', 'tilawa'];

    public function up(): void
    {
        /* Étape 1 : élargir l'ENUM pour accepter aussi la nouvelle valeur */
        foreach (['lignes_rapport', 'taches_memorisation'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->enum('type', ['hifd_jadid', 'murajaa_qariba', 'murajaa_baida', 'tilawa', 'hifd_qadim'])->change();
            });
        }

        /* Étape 2 : convertir les anciennes valeurs */
        foreach (['lignes_rapport', 'taches_memorisation'] as $table) {
            DB::table($table)->whereIn('type', self::ANCIENNES)->update(['type' => 'hifd_qadim']);
        }

        /* Étape 3 : réduire à deux valeurs */
        foreach (['lignes_rapport', 'taches_memorisation'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->enum('type', ['hifd_jadid', 'hifd_qadim'])->change();
            });
        }
    }

    public function down(): void
    {
        foreach (['lignes_rapport', 'taches_memorisation'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->enum('type', ['hifd_jadid', 'murajaa_qariba', 'murajaa_baida', 'tilawa'])->change();
            });
        }
    }
};
