<?php

namespace App\Services;

use App\Enums\NiveauComportement;
use App\Enums\NiveauMoyenne;
use App\Enums\NiveauPeriode;
use App\Enums\StatutPresence;
use App\Enums\TypeRapportPeriodique;
use App\Enums\TypeSeance;
use App\Models\Etudiant;
use App\Models\LigneRapport;
use App\Models\RapportPeriodique;
use Carbon\Carbon;

/**
 * Génère les synthèses périodiques (تقارير دورية : hebdo / mensuel / annuel)
 * d'un étudiant en agrégeant ses rapports journaliers : assiduité, pages
 * de mémorisation et de révision, moyennes qualitatives par type de séance.
 */
class RapportPeriodiqueService
{
    public function __construct(private ProgressionService $progression) {}

    /** Calcule la synthèse de la période contenant $reference puis l'enregistre (création ou mise à jour). */
    public function generer(Etudiant $etudiant, TypeRapportPeriodique $type, Carbon $reference, ?int $auteurId = null): RapportPeriodique
    {
        [$debut, $fin] = match ($type) {
            TypeRapportPeriodique::HEBDOMADAIRE => [$reference->copy()->startOfWeek(), $reference->copy()->endOfWeek()],
            TypeRapportPeriodique::MENSUEL      => [$reference->copy()->startOfMonth(), $reference->copy()->endOfMonth()],
            TypeRapportPeriodique::ANNUEL       => [$reference->copy()->startOfYear(), $reference->copy()->endOfYear()],
        };

        $rapports = $etudiant->rapportsJournaliers()->whereBetween('date', [$debut, $fin])->get();
        $ids      = $rapports->pluck('id');

        // Agrégations LigneRapport par type de séance : note moyenne
        // et total de pages, calculées à la demande pour chaque type.
        $notesParType = fn (TypeSeance $t) => LigneRapport::whereIn('rapport_id', $ids)->where('type', $t)->avg('note');
        $pagesParType = fn (TypeSeance $t) => (float) LigneRapport::whereIn('rapport_id', $ids)->where('type', $t)->sum('nb_pages');

        // La révision (murajaa) correspond désormais au seul hifd_qadim
        // depuis la suppression des anciens types murajaa_qariba/baida/tilawa.
        $pagesMurajaa = $pagesParType(TypeSeance::HIFD_QADIM);
        $moyenneMurajaa = LigneRapport::whereIn('rapport_id', $ids)
            ->where('type', TypeSeance::HIFD_QADIM)
            ->avg('note');

        $moyenneComportement = $rapports->pluck('note_comportement')->filter()
            ->map(fn ($n) => $n instanceof NiveauComportement ? $n->valeur() : NiveauComportement::from((string) $n)->valeur())
            ->avg();

        // Enregistrement final : une seule ligne (etudiant, type, début de période),
        // mise à jour si la synthèse est déjà générée.
        return RapportPeriodique::updateOrCreate(
            ['etudiant_id' => $etudiant->id, 'type' => $type, 'date_debut' => $debut->toDateString()],
            [
                'groupe_id'            => $etudiant->groupe_id,
                'date_fin'             => $fin->toDateString(),
                'nb_seances'           => $rapports->count(),
                'nb_presences'         => $rapports->where('presence', StatutPresence::PRESENT)->count(),
                'nb_absences'          => $rapports->whereIn('presence', [StatutPresence::ABSENT, StatutPresence::EXCUSE])->count(),
                'nb_retards'           => $rapports->where('presence', StatutPresence::RETARD)->count(),
                'total_pages_hifd'     => $pagesParType(TypeSeance::HIFD_JADID),
                'total_pages_murajaa'  => $pagesMurajaa,
                'moyenne_hifd'         => NiveauMoyenne::depuisNote($notesParType(TypeSeance::HIFD_JADID) !== null ? (float) $notesParType(TypeSeance::HIFD_JADID) : null)?->value,
                'moyenne_murajaa'      => NiveauMoyenne::depuisNote($moyenneMurajaa !== null ? (float) $moyenneMurajaa : null)?->value,
                'moyenne_comportement' => NiveauPeriode::depuisNote($moyenneComportement !== false && $moyenneComportement !== null ? (float) $moyenneComportement : null)?->value,
                'genere_par'           => $auteurId,
            ]
        );
    }

    /** Génération de masse (à planifier dans routes/console.php ou un Job). */
    public function genererPourTous(TypeRapportPeriodique $type, ?Carbon $reference = null): int
    {
        $reference = $reference ?? now();
        $compte = 0;

        Etudiant::valides()->whereNotNull('groupe_id')->chunkById(200, function ($etudiants) use ($type, $reference, &$compte) {
            foreach ($etudiants as $etudiant) {
                $this->generer($etudiant, $type, $reference);
                $compte++;
            }
        });

        return $compte;
    }
}
