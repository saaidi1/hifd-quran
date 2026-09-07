<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rapports_periodiques', function (Blueprint $table) {
            $table->enum('type', ['hebdomadaire', 'mensuel', 'annuel'])->change();
            $table->string('moyenne_hifd', 20)->nullable()->change();
            $table->string('moyenne_murajaa', 20)->nullable()->change();
            $table->string('moyenne_comportement', 20)->nullable()->change();
        });

        DB::table('rapports_periodiques')->update([
            'moyenne_hifd'         => DB::raw("CASE WHEN moyenne_hifd IS NULL THEN NULL WHEN moyenne_hifd >= 16 THEN 'excellent' WHEN moyenne_hifd >= 12 THEN 'moyen' ELSE 'faible' END"),
            'moyenne_murajaa'      => DB::raw("CASE WHEN moyenne_murajaa IS NULL THEN NULL WHEN moyenne_murajaa >= 16 THEN 'excellent' WHEN moyenne_murajaa >= 12 THEN 'moyen' ELSE 'faible' END"),
            'moyenne_comportement' => DB::raw("CASE WHEN moyenne_comportement IS NULL THEN NULL WHEN moyenne_comportement >= 16 THEN 'excellent' WHEN moyenne_comportement >= 12 THEN 'moyen' ELSE 'faible' END"),
        ]);

        Schema::table('rapports_periodiques', function (Blueprint $table) {
            $table->dropColumn('moyenne_generale');
        });
    }

    public function down(): void
    {
        Schema::table('rapports_periodiques', function (Blueprint $table) {
            $table->decimal('moyenne_generale', 5, 2)->nullable()->after('moyenne_comportement');
        });

        DB::table('rapports_periodiques')->update([
            'moyenne_hifd'         => DB::raw("CASE WHEN moyenne_hifd = 'excellent' THEN 16 WHEN moyenne_hifd = 'moyen' THEN 12 WHEN moyenne_hifd = 'faible' THEN 8 END"),
            'moyenne_murajaa'      => DB::raw("CASE WHEN moyenne_murajaa = 'excellent' THEN 16 WHEN moyenne_murajaa = 'moyen' THEN 12 WHEN moyenne_murajaa = 'faible' THEN 8 END"),
            'moyenne_comportement' => DB::raw("CASE WHEN moyenne_comportement = 'excellent' THEN 16 WHEN moyenne_comportement = 'moyen' THEN 12 WHEN moyenne_comportement = 'faible' THEN 8 END"),
        ]);

        Schema::table('rapports_periodiques', function (Blueprint $table) {
            $table->enum('type', ['hebdomadaire', 'mensuel'])->change();
            $table->decimal('moyenne_hifd', 5, 2)->nullable()->change();
            $table->decimal('moyenne_murajaa', 5, 2)->nullable()->change();
            $table->decimal('moyenne_comportement', 5, 2)->nullable()->change();
        });
    }
};
