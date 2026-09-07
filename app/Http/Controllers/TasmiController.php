<?php

namespace App\Http\Controllers;

use App\Enums\NiveauComportement;
use App\Enums\StatutPresence;
use App\Enums\TypeSeance;
use App\Models\Groupe;
use App\Models\LigneRapport;
use App\Models\RapportJournalier;
use App\Models\Sourate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Saisie collective des تسميع (récitations) : l'professeur enregistre en une
 * fois la présence d'une halaqa entière ET les lectures de chaque étudiant.
 *
 * Règles métier :
 *  - Chaque enregistrement crée une NOUVELLE séance numérotée max('seance')+1
 *    pour le couple (groupe, date) : plusieurs séances possibles par jour.
 *  - Deux types de lecture par ligne : hifd_jadid (الحفظ الجديد) et
 *    hifd_qadim (الحفظ القديم).
 *  - Le professeur ne saisit que pour SES groupes (403 sinon) ; les autres
 *    rôles peuvent saisir pour n'importe quel groupe.
 */
/** تسجیل يومي جماعي : الأستاذ يسجّل حضور حلقة كاملة وقراءاتها في جلسة واحدة. */
class TasmiController extends Controller
{
    /** Écran principal : groupes accessibles selon rôle, étudiants du groupe choisi, et séances déjà saisies pour (groupe, date) regroupées par étudiant. */
    public function index(Request $request): View
    {
        // Périmètre : un professeur ne voit que ses groupes.
        $groupes = auth()->user()->estProfesseur()
            ? auth()->user()->groupes()->orderBy('nom_ar')->get()
            : Groupe::query()->orderBy('nom_ar')->get();

        $groupeId = $request->input('groupe_id', $groupes->first()?->id);
        $date     = $request->input('date', now()->toDateString());

        $etudiants = collect();
        $existant  = collect();

        if ($groupeId) {
            $groupe = Groupe::with('etudiants')->findOrFail($groupeId);

            // Garde-fou : 403 si un professeur consulte un groupe étranger.
            if (auth()->user()->estProfesseur()) {
                abort_unless($groupe->professeur_id === auth()->id(), 403);
            }

            $etudiants = $groupe->etudiants()->orderBy('nom_ar')->get();
            // Séances déjà enregistrées ce jour-là, triées par numéro de séance puis regroupées par étudiant.
            $existant = RapportJournalier::with('etudiant', 'lignes.sourateDebut', 'lignes.sourateFin')
                ->where('groupe_id', $groupeId)
                ->where('date', $date)
                ->orderBy('seance')
                ->get();

            $nbSeances = $existant->count();
            // Regroupement par étudiant : la vue affiche toutes les séances de chaque élève.
            $existant = $existant->groupBy('etudiant_id');
        }

        return view('tasmi.index', [
            'groupes'   => $groupes,
            'etudiants' => $etudiants,
            'existant'  => $existant,
            'nbSeances' => $nbSeances ?? 0,
            'sourates'  => Sourate::query()->orderBy('numero')->get(),
            'types'     => TypeSeance::cases(),
            'presences' => StatutPresence::cases(),
            'niveaux'   => NiveauComportement::cases(),
            'groupeId'  => $groupeId,
            'date'      => $date,
        ]);
    }

    /** Enregistre une séance collective : validation, contrôle de complétude des lignes de lecture, périmètre professeur, puis création transactionnelle d'un rapport par étudiant coché + ses lignes. */
    public function store(Request $request): RedirectResponse
    {
        $donnees = $request->validate([
            'groupe_id'           => ['required', 'exists:groupes,id'],
            'date'                => ['required', 'date', 'before_or_equal:today'],
            'assister'            => ['nullable', 'array'],
            'presence'            => ['required', 'array'],
            'presence.*'          => [Rule::enum(StatutPresence::class)],
            'note_comportement'   => ['nullable', 'array'],
            'note_comportement.*' => ['nullable', Rule::enum(NiveauComportement::class)],
            'lignes'              => ['nullable', 'array'],
            'lignes.*'            => ['array'],
            'lignes.*.*'          => ['array', $this->lectureComplete()],
            'lignes.*.*.type'             => ['nullable', Rule::enum(TypeSeance::class)],
            'lignes.*.*.sourate_debut_id' => ['nullable', 'exists:sourates,id'],
            'lignes.*.*.ayah_debut'       => ['nullable', 'string', 'max:50'],
            'lignes.*.*.sourate_fin_id'   => ['nullable', 'exists:sourates,id'],
            'lignes.*.*.ayah_fin'         => ['nullable', 'string', 'max:50'],
            'lignes.*.*.nb_pages'         => ['nullable', 'numeric', 'min:0'],
            'lignes.*.*.note'             => ['nullable', 'numeric', 'between:0,20'],
            'lignes.*.*.observation'      => ['nullable', 'string', 'max:2000'],
        ]);

        $groupe = Groupe::findOrFail($donnees['groupe_id']);

        // Le professeur ne saisit que pour ses propres groupes ;
        // le superviseur peut saisir pour n'importe quelle مجموعة.
        if (auth()->user()->estProfesseur()) {
            abort_unless($groupe->professeur_id === auth()->id(), 403);
        }

        // Intersection : seuls les étudiants réellement membres du groupe sont retenus.
        $idsGroupe = $groupe->etudiants()->pluck('id');
        $assister  = collect($donnees['assister'] ?? [])
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->intersect($idsGroupe)
            ->all();

        if (empty($assister)) {
            throw ValidationException::withMessages([
                'assister' => 'يجب اختيار طالب واحد على الأقل للجلسة.',
            ]);
        }

        /* Chaque enregistrement crée une NOUVELLE séance (1, 2, 3...) pour ce groupe et cette date. */
        $seance = $this->prochaineSeance($groupe, $donnees['date']);

        // Transaction : soit tous les rapports de la séance sont créés, soit aucun.
        DB::transaction(function () use ($donnees, $groupe, $assister, $seance) {
            foreach ($assister as $etudiantId) {
                if (! array_key_exists($etudiantId, $donnees['presence'] ?? [])) {
                    continue;
                }

                $rapport = RapportJournalier::create([
                    'etudiant_id'       => $etudiantId,
                    'professeur_id'     => auth()->id(),
                    'groupe_id'         => $groupe->id,
                    'date'              => $donnees['date'],
                    'seance'            => $seance,
                    'presence'          => $donnees['presence'][$etudiantId],
                    'note_comportement' => $donnees['note_comportement'][$etudiantId] ?? null,
                ]);

                $this->enregistrerLectures($rapport, $donnees['lignes'][$etudiantId] ?? []);
                $rapport->recalculerNoteGlobale();
            }
        });

        return redirect()->route('tasmi.index', ['groupe_id' => $groupe->id, 'date' => $donnees['date']])
            ->with('success', __('Session recorded successfully.'));
    }

    /** Enregistre chaque lecture de l'étudiant comme une ligne (LigneRapport) rattachée au rapport. */
    private function enregistrerLectures(RapportJournalier $rapport, array $lectures): void
    {
        foreach ($lectures as $ligne) {
            // Une ligne vide n'est pas une lecture : on la saute.
            if (empty($ligne['type'])) {
                continue;
            }

            LigneRapport::create([
                'rapport_id'       => $rapport->id,
                'type'             => $ligne['type'],
                'sourate_debut_id' => $ligne['sourate_debut_id'],
                'ayah_debut'       => $ligne['ayah_debut'],
                'sourate_fin_id'   => $ligne['sourate_fin_id'],
                'ayah_fin'         => $ligne['ayah_fin'],
                'nb_pages'         => $ligne['nb_pages'] ?? null,
                'note'             => $ligne['note'] ?? null,
                'observation'      => $ligne['observation'] ?? null,
            ]);
        }
    }

    /**
     * Règle de validation d'une lecture (une ligne de تسميع).
     *     * Une lecture est « commencée » dès que son type ou sa sourate de début
     * est renseigné. Dans ce cas, les 4 champs d'intervalle (sourate de début,
     * verset de début, sourate de fin, verset de fin) deviennent obligatoires.
     * Une ligne totalement vide est simplement ignorée.
     */
    private function lectureComplete(): callable
    {
        return function (string $attribut, mixed $valeur, \Closure $echec) {
            // Ligne vide => pas une lecture => aucune vérification.
            if (empty($valeur['type']) && empty($valeur['sourate_debut_id'])) {
                return;
            }

            $attendus = ['sourate_debut_id', 'ayah_debut', 'sourate_fin_id', 'ayah_fin'];

            foreach ($attendus as $champ) {
                if (empty($valeur[$champ] ?? '')) {
                    $echec('هذه الخانة مطلوبة لإكمال القراءة.');
                }
            }
        };
    }

    /** Calcule le numéro de la prochaine séance pour (groupe, date) : max + 1. */
    private function prochaineSeance(Groupe $groupe, string $date): int
    {
        return (int) RapportJournalier::where('groupe_id', $groupe->id)
            ->whereDate('date', $date)
            ->max('seance') + 1;
    }
}
