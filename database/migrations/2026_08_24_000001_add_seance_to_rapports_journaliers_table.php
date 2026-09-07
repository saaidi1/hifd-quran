<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Plusieurs séances de تسميع par jour : colonne « seance » + unicité (étudiant, date, séance). */
return new class extends Migration
{
    private function indexExiste(string $nom): bool
    {
        return collect(DB::select('SHOW INDEX FROM rapports_journaliers WHERE Key_name = ?', [$nom]))->isNotEmpty();
    }

    public function up(): void
    {
        if (! Schema::hasColumn('rapports_journaliers', 'seance')) {
            Schema::table('rapports_journaliers', function (Blueprint $table) {
                $table->unsignedTinyInteger('seance')->default(1)->after('date');
            });
        }

        /* Nouvel index créé AVANT la suppression de l'ancien : la clé étrangère
           etudiant_id exige un index commençant par cette colonne (erreur 1553). */
        if (! $this->indexExiste('rapports_journaliers_etudiant_id_date_seance_unique')) {
            Schema::table('rapports_journaliers', function (Blueprint $table) {
                $table->unique(['etudiant_id', 'date', 'seance'], 'rapports_journaliers_etudiant_id_date_seance_unique');
            });
        }

        if ($this->indexExiste('rapports_journaliers_etudiant_id_date_unique')) {
            Schema::table('rapports_journaliers', function (Blueprint $table) {
                $table->dropUnique('rapports_journaliers_etudiant_id_date_unique');
            });
        }
    }

    public function down(): void
    {
        /* L'ancienne unicité n'est restaurable que si aucune séance multiple n'existe. */
        $doublons = DB::table('rapports_journaliers')
            ->selectRaw('etudiant_id, date')
            ->groupBy('etudiant_id', 'date')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($doublons->isEmpty() && ! $this->indexExiste('rapports_journaliers_etudiant_id_date_unique')) {
            Schema::table('rapports_journaliers', function (Blueprint $table) {
                $table->unique(['etudiant_id', 'date'], 'rapports_journaliers_etudiant_id_date_unique');
            });
        }

        if ($this->indexExiste('rapports_journaliers_etudiant_id_date_seance_unique')) {
            Schema::table('rapports_journaliers', function (Blueprint $table) {
                $table->dropUnique('rapports_journaliers_etudiant_id_date_seance_unique');
            });
        }

        if (Schema::hasColumn('rapports_journaliers', 'seance')) {
            Schema::table('rapports_journaliers', function (Blueprint $table) {
                $table->dropColumn('seance');
            });
        }
    }
};
