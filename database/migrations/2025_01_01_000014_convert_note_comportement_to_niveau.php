<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rapports_journaliers', function (Blueprint $table) {
            $table->string('note_comportement', 20)->nullable()->change();
        });

        DB::table('rapports_journaliers')
            ->whereNotNull('note_comportement')
            ->update([
                'note_comportement' => DB::raw("CASE
                    WHEN note_comportement >= 17.5 THEN 'excellent'
                    WHEN note_comportement >= 14   THEN 'bon'
                    WHEN note_comportement >= 10   THEN 'amelioration'
                    WHEN note_comportement IS NOT NULL THEN 'faible'
                END"),
            ]);
    }

    public function down(): void
    {
        DB::table('rapports_journaliers')
            ->whereNotNull('note_comportement')
            ->update([
                'note_comportement' => DB::raw("CASE note_comportement
                    WHEN 'excellent'    THEN 20
                    WHEN 'bon'          THEN 15
                    WHEN 'amelioration' THEN 12
                    WHEN 'faible'       THEN 5
                    ELSE NULL
                END"),
            ]);

        Schema::table('rapports_journaliers', function (Blueprint $table) {
            $table->decimal('note_comportement', 5, 2)->nullable()->change();
        });
    }
};
