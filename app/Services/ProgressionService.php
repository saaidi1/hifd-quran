<?php

namespace App\Services;

use App\Enums\TypeSeance;
use App\Models\Etudiant;
use App\Models\LigneRapport;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Calculs de progression du hifd (الحفظ) : versets mémorisés, pourcentage
 * du mushaf, pages faites et restantes, rythme journalier et alertes.
 * S'appuie uniquement sur les lignes de rapports journaliers validées.
 */
class ProgressionService
{
    /** Références canoniques du mushaf de Médine (604 pages, 6 236 versets). */
    public const TOTAL_VERSETS = 6236;
    public const TOTAL_PAGES   = 604;   // mushaf de Médine
    public const TOTAL_HIZB    = 60;

    /** Nombre de versets distincts mémorisés (hifd jadid validé, note >= 10). */
    public function versetsMemorises(Etudiant $etudiant): int
    {
        $lignes = LigneRapport::query()
            ->whereHas('rapport', fn ($q) => $q->where('etudiant_id', $etudiant->id))
            ->where('type', TypeSeance::HIFD_JADID)
            ->where('note', '>=', 10)
            ->with(['sourateDebut', 'sourateFin'])
            ->get();

        // Fusion des intervalles pour ne pas compter deux fois une portion révisée.
        $intervalles = $lignes->map(fn (LigneRapport $l) => [
            $l->sourateDebut->positionGlobale($l->ayah_debut),
            $l->sourateFin->positionGlobale($l->ayah_fin),
        ])->sortBy(0)->values()->all();

        $total = 0;
        $courant = null;

        foreach ($intervalles as [$debut, $fin]) {
            if ($courant === null) {
                $courant = [$debut, $fin];
                continue;
            }
            if ($debut <= $courant[1] + 1) {
                $courant[1] = max($courant[1], $fin);
            } else {
                $total += $courant[1] - $courant[0] + 1;
                $courant = [$debut, $fin];
            }
        }

        if ($courant !== null) {
            $total += $courant[1] - $courant[0] + 1;
        }

        return $total;
    }

    /** Pourcentage du Coran mémorisé (base 6 236 versets). */
    public function pourcentage(Etudiant $etudiant): float
    {
        return round($this->versetsMemorises($etudiant) * 100 / self::TOTAL_VERSETS, 2);
    }

    /** Pages cumulées saisies par le professeur sur une période, pour un type de séance donné. */
    public function pagesCumulees(Etudiant $etudiant, TypeSeance $type, $debut, $fin): float
    {
        return (float) LigneRapport::query()
            ->whereHas('rapport', fn ($q) => $q->where('etudiant_id', $etudiant->id)->whereBetween('date', [$debut, $fin]))
            ->where('type', $type)
            ->sum('nb_pages');
    }

    /** Rythme moyen (pages/jour) sur les N derniers jours — utile au dashboard. */
    public function rythmeHebdomadaire(Etudiant $etudiant, int $jours = 7): float
    {
        $pages = $this->pagesCumulees(
            $etudiant, TypeSeance::HIFD_JADID, now()->subDays($jours)->toDateString(), now()->toDateString()
        );

        return round($pages / max(1, $jours), 2);
    }

    /** Étudiants en difficulté : moyenne < seuil sur la période. */
    public function alertes(CarbonInterface $debut, CarbonInterface $fin, float $seuil = 10)
    {
        return DB::table('rapports_journaliers')
            ->select('etudiant_id', DB::raw('AVG(note_globale) as moyenne'), DB::raw('SUM(presence = "absent") as absences'))
            ->whereBetween('date', [$debut, $fin])
            ->groupBy('etudiant_id')
            ->having('moyenne', '<', $seuil)
            ->get();
    }
}
