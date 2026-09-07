<?php

namespace App\Http\Controllers;

use App\Enums\NiveauComportement;
use App\Enums\StatutPresence;
use App\Enums\TypeSeance;
use App\Exports\RapportsJournaliersExport;
use App\Models\Etudiant;
use App\Models\LigneRapport;
use App\Models\RapportJournalier;
use App\Models\Sourate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

/**
 * CRUD des rapports journaliers individuels (سجل يومي) saisis par le professeur
 * pour un seul étudiant (le TasmiController gère la saisie collective).
 *
 * Règles métier :
 *  - Seul un professeur crée/modifie ses rapports ; chaque enregistrement crée
 *    une NOUVELLE séance numérotée max('seance')+1 pour l'étudiant et la date.
 *  - L'étudiant doit appartenir à un groupe du professeur connecté.
 *  - Directeur/superviseur/garde consultent tout ; le directeur peut supprimer.
 *  - Deux types de lecture possibles par ligne : hifd_jadid / hifd_qadim.
 */
class RapportJournalierController extends Controller
{
    /** Liste paginée des rapports du mois en cours par défaut, avec filtres dates + recherche étudiant. Un professeur ne voit que les rapports de SES groupes. */
    public function index(Request $request): View
    {
        $moisDebut = now()->startOfMonth();
        $moisFin   = now()->endOfMonth();

        $query = RapportJournalier::query()
            ->with(['etudiant', 'groupe', 'professeur'])
            ->whereBetween('date', [$request->input('debut', $moisDebut->toDateString()), $request->input('fin', $moisFin->toDateString())]);

        if ($terme = trim((string) $request->input('q'))) {
            // Recherche sur l'étudiant lié (prénom/nom arabe-latin, matricule) via le scope.
            $query->whereHas('etudiant', fn (Builder $b) => $b->recherche($terme));
        }

        if (auth()->user()->estProfesseur()) {
            // Périmètre professeur : uniquement les rapports de ses groupes.
            $query->whereIn('groupe_id', auth()->user()->groupes()->pluck('groupes.id'));
        }

        $rapports = $query->orderByDesc('date')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('rapport-journaliers.index', [
            'rapports' => $rapports,
            'filtres'  => $request->only(['q', 'debut', 'fin']),
        ]);
    }

    /** Formulaire de saisie individuelle. Réservé aux professeurs (403 sinon) ; liste uniquement les étudiants ayant un groupe. */
    public function create(): View
    {
        abort_unless(auth()->user()->estProfesseur(), 403);

        // Uniquement les étudiants déjà rattachés à un groupe (on ne peut pas
        // saisir un rapport pour un étudiant sans حلقة).
        $etudiants = auth()->user()->etudiants()
            ->whereNotNull('groupe_id')
            ->with(['groupe'])
            ->orderBy('nom_ar')
            ->get();

        return view('rapport-journaliers.create', [
            'etudiants'  => $etudiants,
            'sourates'   => Sourate::query()->orderBy('numero')->get(),
            'types'      => TypeSeance::cases(),
            'presences'  => StatutPresence::cases(),
            'niveaux'    => NiveauComportement::cases(),
            'dateJour'   => now()->toDateString(),
            'existant'   => collect(),
        ]);
    }

    /** Enregistre un rapport individuel : vérifie que l'étudiant appartient à un groupe du professeur, calcule le numéro de séance suivant, crée rapport + lignes de lecture puis recalcule la note globale. */
    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->estProfesseur(), 403);

        $donnees = $request->validate([
            'etudiant_id'      => ['required', 'exists:etudiants,id'],
            'date'             => ['required', 'date', 'before_or_equal:today'],
            'presence'         => ['required', Rule::enum(StatutPresence::class)],
            'heure_arrivee'    => ['nullable', 'date_format:H:i'],
            'note_comportement'=> ['nullable', Rule::enum(NiveauComportement::class)],
            'remarques'        => ['nullable', 'string', 'max:2000'],
            'lignes'           => ['nullable', 'array'],
            'lignes.*.type'             => ['required', Rule::enum(TypeSeance::class)],
            'lignes.*.sourate_debut_id' => ['required', 'exists:sourates,id'],
            'lignes.*.ayah_debut'       => ['required', 'string', 'max:50'],
            'lignes.*.sourate_fin_id'   => ['required', 'exists:sourates,id'],
            'lignes.*.ayah_fin'         => ['required', 'string', 'max:50'],
            'lignes.*.nb_pages'         => ['nullable', 'numeric', 'min:0'],
            'lignes.*.note'             => ['nullable', 'numeric', 'between:0,20'],
            'lignes.*.nb_erreurs'       => ['nullable', 'integer', 'min:0'],
            'lignes.*.nb_hesitations'   => ['nullable', 'integer', 'min:0'],
            'lignes.*.observation'      => ['nullable', 'string', 'max:2000'],
        ]);

        $etudiant = Etudiant::findOrFail($donnees['etudiant_id']);
        $groupeId = $etudiant->groupe_id;

        // Contrôle métier : l'étudiant doit être rattaché à un groupe du professeur connecté.
        if (! $groupeId || ! auth()->user()->groupes()->whereKey($groupeId)->exists()) {
            return back()->withErrors(['etudiant_id' => 'الطالب غير تابع لمجموعة من مجموعاتك.'])->withInput();
        }

        /* Plusieurs séances possibles par jour : on crée la séance suivante. */
        $seance = (int) RapportJournalier::where('etudiant_id', $etudiant->id)
            ->where('date', $donnees['date'])
            ->max('seance') + 1;

        $rapport = RapportJournalier::create([
            'etudiant_id'       => $etudiant->id,
            'professeur_id'     => auth()->id(),
            'groupe_id'         => $groupeId,
            'date'              => $donnees['date'],
            'seance'            => $seance,
            'presence'          => $donnees['presence'],
            'heure_arrivee'     => $donnees['heure_arrivee'] ?? null,
            'note_comportement' => $donnees['note_comportement'] ?? null,
            'remarques'         => $donnees['remarques'] ?? null,
        ]);

        $this->enregistrerLignes($rapport, $donnees['lignes'] ?? []);
        $rapport->recalculerNoteGlobale();

        return redirect()->route('rapport-journaliers.show', $rapport)
            ->with('success', __('Daily report saved successfully.'));
    }

    /** Affiche un rapport détaillé avec ses lignes de lecture. 403 pour un professeur hors de son périmètre. */
    public function show(RapportJournalier $rapport): View
    {
        $this->verifierAcces($rapport);

        return view('rapport-journaliers.show', [
            'rapport' => $rapport->load(['etudiant', 'groupe', 'professeur', 'lignes.tache', 'lignes.sourateDebut', 'lignes.sourateFin']),
        ]);
    }

    /** Télécharge le rapport journalier au format PDF (justificatif à imprimer). */
    public function telechargerPdf(RapportJournalier $rapport)
    {
        $this->verifierAcces($rapport);

        $pdf = Pdf::loadView('rapport-journaliers.pdf', [
            'rapport' => $rapport->load(['etudiant', 'groupe', 'professeur', 'lignes.sourateDebut', 'lignes.sourateFin']),
        ]);

        return $pdf->download('rapport-journalier-'.$rapport->date->format('Y-m-d').'-'.$rapport->etudiant_id.'.pdf');
    }

    /** Exporte la liste filtrée courante des rapports journaliers au format Excel (périmètre professeur respecté). */
    public function exporterExcel(Request $request)
    {
        $filtres = $request->only(['q', 'debut', 'fin']);

        // Un professeur n'exporte que les rapports de ses groupes.
        $groupesIds = auth()->user()->estProfesseur()
            ? auth()->user()->groupes()->pluck('groupes.id')->all()
            : null;

        return Excel::download(new RapportsJournaliersExport($filtres, $groupesIds), 'rapports-journaliers.xlsx');
    }

    /** Formulaire d'édition d'un rapport existant (étudiants limités au périmètre du professeur). */
    public function edit(RapportJournalier $rapport): View
    {
        $this->verifierAcces($rapport);

        return view('rapport-journaliers.edit', [
            'rapport'    => $rapport->load('lignes'),
            'etudiants'  => auth()->user()->etudiants()->with('groupe')->orderBy('nom_ar')->get(),
            'sourates'   => Sourate::query()->orderBy('numero')->get(),
            'types'      => TypeSeance::cases(),
            'presences'  => StatutPresence::cases(),
            'niveaux'    => NiveauComportement::cases(),
        ]);
    }

    /** Met à jour l'en-tête du rapport puis remplace intégralement les lignes (delete + re-création) et recalcule la note globale. */
    public function update(Request $request, RapportJournalier $rapport): RedirectResponse
    {
        $this->verifierAcces($rapport);

        $donnees = $request->validate([
            'presence'          => ['required', Rule::enum(StatutPresence::class)],
            'heure_arrivee'     => ['nullable', 'date_format:H:i'],
            'note_comportement' => ['nullable', Rule::enum(NiveauComportement::class)],
            'remarques'         => ['nullable', 'string', 'max:2000'],
            'lignes'            => ['nullable', 'array'],
            'lignes.*.type'             => ['required', Rule::enum(TypeSeance::class)],
            'lignes.*.sourate_debut_id' => ['required', 'exists:sourates,id'],
            'lignes.*.ayah_debut'       => ['required', 'string', 'max:50'],
            'lignes.*.sourate_fin_id'   => ['required', 'exists:sourates,id'],
            'lignes.*.ayah_fin'         => ['required', 'string', 'max:50'],
            'lignes.*.nb_pages'         => ['nullable', 'numeric', 'min:0'],
            'lignes.*.note'             => ['nullable', 'numeric', 'between:0,20'],
            'lignes.*.nb_erreurs'       => ['nullable', 'integer', 'min:0'],
            'lignes.*.nb_hesitations'   => ['nullable', 'integer', 'min:0'],
            'lignes.*.observation'      => ['nullable', 'string', 'max:2000'],
        ]);

        $rapport->update([
            'presence'          => $donnees['presence'],
            'heure_arrivee'     => $donnees['heure_arrivee'] ?? null,
            'note_comportement' => $donnees['note_comportement'] ?? null,
            'remarques'         => $donnees['remarques'] ?? null,
        ]);

        // Stratégie « delete & recreate » : les lignes sont toujours remplacées en bloc.
        $rapport->lignes()->delete();
        $this->enregistrerLignes($rapport, $donnees['lignes'] ?? []);
        $rapport->recalculerNoteGlobale();

        return redirect()->route('rapport-journaliers.show', $rapport)
            ->with('success', __('Report updated.'));
    }

    /** Supprime le rapport (et ses lignes en cascade). Accès : professeur propriétaire via verifierAcces, directeur. */
    public function destroy(RapportJournalier $rapport): RedirectResponse
    {
        $this->verifierAcces($rapport);

        $rapport->delete();

        return redirect()->route('rapport-journaliers.index')->with('success', __('Report deleted.'));
    }

    /** Crée les lignes de lecture (hifd_jadid / hifd_qadim) rattachées au rapport ; les lignes sans type sont ignorées. */
    private function enregistrerLignes(RapportJournalier $rapport, array $lignes): void
    {
        foreach ($lignes as $ligne) {
            if (empty($ligne['type'])) {
                continue;
            }
            LigneRapport::create([
                'rapport_id'        => $rapport->id,
                'type'              => $ligne['type'],
                'sourate_debut_id'  => $ligne['sourate_debut_id'],
                'ayah_debut'        => $ligne['ayah_debut'],
                'sourate_fin_id'    => $ligne['sourate_fin_id'],
                'ayah_fin'          => $ligne['ayah_fin'],
                'nb_pages'          => $ligne['nb_pages'] ?? null,
                'note'              => $ligne['note'] ?? null,
                'nb_erreurs'        => $ligne['nb_erreurs'] ?? 0,
                'nb_hesitations'    => $ligne['nb_hesitations'] ?? 0,
                'observation'       => $ligne['observation'] ?? null,
            ]);
        }
    }

    /** Garde-fou : 403 si un professeur accède à un rapport d'un groupe qui n'est pas le sien. */
    private function verifierAcces(RapportJournalier $rapport): void
    {
        if (auth()->user()->estProfesseur()) {
            abort_unless($rapport->groupe && auth()->user()->groupes()->whereKey($rapport->groupe_id)->exists(), 403);
        }
    }
}
