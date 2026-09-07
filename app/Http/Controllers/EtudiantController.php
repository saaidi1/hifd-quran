<?php

namespace App\Http\Controllers;

use App\Enums\StatutInscription;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Models\Groupe;
use App\Models\LieuPriere;
use App\Models\PlacePriere;
use App\Services\InscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Gestion du cycle de vie d'un étudiant : liste, pré-inscription, dossier,
 * puis les 4 étapes du workflow d'admission :
 *   1. Évaluation (test) par le SUPERVISEUR (المشرف) → statut validé/refusé/ajourné.
 *   2. Affectation à une halaqa par le GARDE (ou directeur).
 *   3. Hébergement : attribution du lit (numéro de lit = matricule) — GARDE.
 *   4. Place de prière : numéro de place = matricule — GARDE.
 *
 * Sécurité : le professeur ne voit/modifie que les étudiants de SES groupes
 * (voir verifierAcces()) ; les autres rôles voient tout.
 */
class EtudiantController extends Controller
{
    /** Injection du service métier InscriptionService (pré-inscription, évaluation, affectation). */
    public function __construct(private InscriptionService $service) {}

    /* ---------------- Liste ---------------- */

    /** Liste paginée des étudiants avec filtres (statut, groupe, interne, sans groupe, recherche). Un professeur ne reçoit que les étudiants de ses groupes. */
    public function index(Request $request): View
    {
        $query = Etudiant::query()->with(['groupe.professeur', 'hebergement']);

        // Périmètre professeur : restriction SQL à ses propres groupes (sous-requête).
        if (auth()->user()->estProfesseur()) {
            $query->whereIn('groupe_id', auth()->user()->groupes()->select('id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->string('statut'));
        }

        if ($request->filled('groupe_id')) {
            $query->where('groupe_id', $request->integer('groupe_id'));
        }

        if ($request->filled('interne')) {
            $query->where('interne', $request->boolean('interne'));
        }

        if ($request->filled('sans_groupe')) {
            $query->whereNull('groupe_id')->where('statut', StatutInscription::VALIDE);
        }

        if ($request->filled('q')) {
            // Recherche multi-champs (nom, prénom, matricule) via le scope du modèle.
            $query->recherche($request->string('q'));
        }

        $etudiants = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('etudiants.index', [
            'etudiants' => $etudiants,
            'groupes'   => Groupe::where('actif', true)->orderBy('nom_ar')->get(),
        ]);
    }

    /* ---------------- Création (pré-inscription) ---------------- */

    /** Formulaire de pré-inscription d'un nouvel étudiant. */
    public function create(): View
    {
        return view('etudiants.create');
    }

    /** Enregistre une pré-inscription : validation, upload des fichiers, puis délégation au service (génère le matricule). Redirige vers la fiche étudiant. */
    public function store(Request $request): RedirectResponse
    {
        $donnees = $this->validerDonnees($request);

        $donnees = $this->gererFichiers($request, $donnees);

        $etudiant = $this->service->preinscrire($donnees, auth()->user());

        return redirect()->route('etudiants.show', $etudiant)
            ->with('success', __('Student provisionally registered. Registration number: :number', ['number' => $etudiant->matricule]));
    }

    /* ---------------- Dossier complet ---------------- */

    /** Affiche la fiche complète d'un étudiant (évaluations, rapports, comportements, tâches, hébergement, place de prière). Accès restreint pour les professeurs. */
    public function show(Etudiant $etudiant): View
    {
        $this->verifierAcces($etudiant);

        // Eager loading massif pour éviter les requêtes N+1 dans la fiche détaillée.
        $etudiant->load([
            'evaluations.sourateTestee',
            'rapportsJournaliers.lignes.sourateDebut',
            'rapportsJournaliers.lignes.sourateFin',
            'comportements.signalePar',
            'taches.sourateDebut',
            'taches.sourateFin',
            'hebergement.chambre',
            'placePriere.lieuPriere',
            'groupe.professeur',
        ]);

        return view('etudiants.show', ['etudiant' => $etudiant]);
    }

    /* ---------------- Édition ---------------- */

    /** Formulaire d'édition du dossier. Accès restreint pour les professeurs. */
    public function edit(Etudiant $etudiant): View
    {
        $this->verifierAcces($etudiant);

        return view('etudiants.edit', ['etudiant' => $etudiant]);
    }

    /** Met à jour le dossier après validation et gestion des fichiers uploadés. Accès restreint pour les professeurs. */
    public function update(Request $request, Etudiant $etudiant): RedirectResponse
    {
        $this->verifierAcces($etudiant);

        $donnees = $this->validerDonnees($request, $etudiant->id);
        $donnees = $this->gererFichiers($request, $donnees);

        $etudiant->update($donnees);

        return redirect()->route('etudiants.show', $etudiant)
            ->with('success', __('Student information updated.'));
    }

    /* ---------------- Workflow : évaluation (المشرف) ---------------- */

    /** Enregistre la décision du superviseur (valide/refuse/ajourne) après test. Réservé au superviseur (403 sinon) ; 409 si le statut actuel est déjà validé ou refusé. Le motif est obligatoire en cas de refus. */
    public function evaluer(Request $request, Etudiant $etudiant): RedirectResponse
    {
        abort_unless(auth()->user()->estSuperviseur(), 403);
        // 409 Conflit : on ne ré-évalue pas un dossier déjà tranché.
        abort_if(in_array($etudiant->statut, [StatutInscription::VALIDE, StatutInscription::REFUSE], true), 409);

        $donnees = $request->validate([
            'date_test'         => ['required', 'date', 'before_or_equal:today'],
            'sourate_testee_id' => ['nullable', 'exists:sourates,id'],
            'hizb_maitrise'     => ['required', 'numeric', 'min:0', 'max:60'],
            'note_hifd'         => ['required', 'numeric', 'min:0', 'max:20'],
            'note_tajwid'       => ['required', 'numeric', 'min:0', 'max:20'],
            'note_lecture'      => ['required', 'numeric', 'min:0', 'max:20'],
            'decision'          => ['required', 'in:valide,refuse,ajourne'],
            'niveau_propose'    => ['nullable', 'string'],
            'motif'             => ['nullable', 'string'],
            'observations'      => ['nullable', 'string'],
        ], [
            'decision.in' => 'القرار غير صالح.',
        ]);

        if ($donnees['decision'] === 'refuse' && blank($donnees['motif'] ?? null)) {
            throw ValidationException::withMessages(['motif' => 'سبب الرفض مطلوب عند رفض الطالب.']);
        }

        $this->service->evaluer($etudiant, $donnees, auth()->user());

        $message = $donnees['decision'] === 'valide'
            ? __('Decision recorded: accepted. The general guard can now assign the student to a halaqa.')
            : __('Decision recorded. The student will not be assigned to any halaqa.');

        return redirect()->route('etudiants.show', $etudiant)->with('success', $message);
    }

    /* ---------------- Workflow : affectation (الحارس) ---------------- */

    /** Affecte un étudiant validé à une halaqa. Réservé au garde et au directeur (403 sinon) ; les erreurs métier (groupe plein, statut invalide) remontent du service. */
    public function affecter(Request $request, Etudiant $etudiant): RedirectResponse
    {
        abort_unless(auth()->user()->estGarde() || auth()->user()->estDirecteur(), 403);

        $donnees = $request->validate([
            'groupe_id' => ['required', 'exists:groupes,id'],
            'motif'     => ['nullable', 'string'],
        ]);

        try {
            $this->service->affecter($etudiant, Groupe::findOrFail($donnees['groupe_id']), auth()->user(), $donnees['motif'] ?? null);

            return redirect()->route('etudiants.show', $etudiant)->with('success', __('Assigned to the halaqa.'));
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }
    }

    /** Affectation en masse : chaque étudiant est traité individuellement ; les échecs sont comptés (ko) sans interrompre la boucle. Réservé garde/directeur. */
    public function affecterMultiple(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->estGarde() || auth()->user()->estDirecteur(), 403);

        $donnees = $request->validate([
            'ids'       => ['required', 'array', 'min:1'],
            'ids.*'     => ['exists:etudiants,id'],
            'groupe_id' => ['required', 'exists:groupes,id'],
        ]);

        $groupe = Groupe::findOrFail($donnees['groupe_id']);
        $ok = 0;
        $ko = 0;

        foreach ($donnees['ids'] as $id) {
            try {
                $this->service->affecter(Etudiant::findOrFail($id), $groupe, auth()->user());
                $ok++;
            } catch (ValidationException) {
                $ko++;
            }
        }

        return back()->with('success', __(':count students assigned to the halaqa', ['count' => $ok])
            . ($ko ? '، ' . __(':count could not be assigned (registration not validated or the halaqa is full).', ['count' => $ko]) : '.'));
    }

    /* ---------------- Workflow : hébergement (الحارس) ---------------- */

    /** Attribue un lit en internat. Règle métier : le numéro de lit = numéro d'inscription (matricule), donc le lit correspondant doit être libre dans la chambre (403 si non-garde, exception sinon). Désactive l'hébergement précédent. */
    public function hebergement(Request $request, Etudiant $etudiant): RedirectResponse
    {
        abort_unless(auth()->user()->estGarde(), 403);

        $donnees = $request->validate([
            'chambre_id'  => ['required', 'exists:chambres,id'],
            'observation' => ['nullable', 'string'],
        ]);

        $chambre = Chambre::findOrFail($donnees['chambre_id']);
        // Le matricule détermine le lit : pas de choix arbitraire possible.
        $numero  = $etudiant->numeroInscription();

        if (! in_array($numero, $chambre->litsLibres(), true)) {
            throw ValidationException::withMessages([
                'chambre_id' => "السرير رقم {$numero} (المطابق لرقم التسجيل) غير متاح في هذه الغرفة.",
            ]);
        }

        // Clôture de l'hébergement précédent avant d'en créer un nouveau (historique conservé).
        $etudiant->hebergement?->update(['actif' => false, 'date_fin' => now()->toDateString()]);

        $etudiant->hebergement()->create([
            'chambre_id'   => $donnees['chambre_id'],
            'numero_lit'   => $numero,
            'date_debut'   => now()->toDateString(),
            'attribue_par' => auth()->id(),
            'observation'  => $donnees['observation'] ?? null,
            'actif'        => true,
        ]);

        $etudiant->update(['interne' => true]);

        return back()->with('success', __('Bed number :number assigned successfully.', ['number' => $numero]));
    }

    /* ---------------- Workflow : place de prière (الحارس) ---------------- */

    /** Attribue une place de prière dans un lieu donné. Règle métier : numéro de place = matricule ; erreur si la place est déjà occupée activement. Réservé au garde (403 sinon). */
    public function placePriere(Request $request, Etudiant $etudiant): RedirectResponse
    {
        abort_unless(auth()->user()->estGarde(), 403);

        $donnees = $request->validate([
            'lieu_priere_id' => ['required', 'exists:lieux_priere,id'],
            'rangee'         => ['required', 'integer', 'min:1'],
        ]);

        $numero = $etudiant->numeroInscription();

        // Conflit si la même place est active dans ce lieu pour un autre étudiant.
        $occupe = PlacePriere::where('lieu_priere_id', $donnees['lieu_priere_id'])
            ->where('actif', true)
            ->where('numero_place', $numero)
            ->when($etudiant->placePriere, fn ($q) => $q->whereKeyNot($etudiant->placePriere->id))
            ->exists();

        if ($occupe) {
            throw ValidationException::withMessages([
                'lieu_priere_id' => "المكان رقم {$numero} (المطابق لرقم التسجيل) محجوز بالفعل في هذا المصلى.",
            ]);
        }

        $etudiant->placePriere?->update(['actif' => false, 'date_fin' => now()->toDateString()]);

        $etudiant->placePriere()->create([
            'lieu_priere_id' => $donnees['lieu_priere_id'],
            'rangee'         => $donnees['rangee'],
            'numero_place'   => $numero,
            'date_debut'     => now()->toDateString(),
            'attribue_par'   => auth()->id(),
            'actif'          => true,
        ]);

        return back()->with('success', __('Prayer place number :number assigned.', ['number' => $numero]));
    }

    /* ---------------- Aides ---------------- */

    /** Garde-fou périmètre professeur : 403 si l'étudiant n'appartient à aucun des groupes du professeur connecté. Les autres rôles passent. */
    private function verifierAcces(Etudiant $etudiant): void
    {
        if (auth()->user()->estProfesseur()) {
            abort_unless($etudiant->groupe_id && in_array($etudiant->groupe_id, auth()->user()->groupes()->pluck('id')->all(), true), 403);
        }
    }

    /** Stocke les fichiers reçus et remplace les clés par leurs chemins. */
    private function gererFichiers(Request $request, array $donnees): array
    {
        $fichiers = ['photo' => 'etudiants', 'extrait_naissance' => 'documents/naissances',
                     'attestation_scolaire' => 'documents/attestations', 'autre_document' => 'documents/autres'];

        foreach ($fichiers as $champ => $dossier) {
            if ($request->hasFile($champ)) {
                $donnees[$champ] = $request->file($champ)->store($dossier, 'public');
            } else {
                unset($donnees[$champ]);
            }
        }

        return $donnees;
    }

    /** Règles de validation communes à store() et update() ; $ignore sert à exclure l'étudiant en cours (uniquement pour les règles uniques éventuelles). */
    private function validerDonnees(Request $request, ?int $ignore = null): array
    {
        return $request->validate([
            'nom_ar'             => ['required', 'string', 'max:100'],
            'prenom_ar'          => ['required', 'string', 'max:100'],
            'nom'                => ['nullable', 'string', 'max:100'],
            'prenom'             => ['nullable', 'string', 'max:100'],
            'sexe'               => ['required', 'in:M,F'],
            'date_naissance'     => ['required', 'date', 'before_or_equal:today'],
            'lieu_naissance'     => ['nullable', 'string', 'max:255'],
            'cin'                => ['nullable', 'string', 'max:30'],
            'telephone'          => ['nullable', 'string', 'max:30'],
            'ville'              => ['nullable', 'string', 'max:255'],
            'niveau_scolaire'    => ['nullable', 'string', 'max:255'],
            'adresse'            => ['nullable', 'string'],
            'photo'              => ['nullable', 'image', 'max:2048'],
            'extrait_naissance'  => ['nullable', 'file', 'max:4096', 'mimes:pdf,jpg,jpeg,png'],
            'attestation_scolaire' => ['nullable', 'file', 'max:4096', 'mimes:pdf,jpg,jpeg,png'],
            'autre_document'     => ['nullable', 'file', 'max:4096', 'mimes:pdf,jpg,jpeg,png'],
            'tuteur_nom'         => ['required', 'string', 'max:255'],
            'tuteur_lien'        => ['nullable', 'string', 'max:50'],
            'tuteur_telephone'   => ['required', 'string', 'max:30'],
            'hifd_initial_hizb'  => ['nullable', 'numeric', 'min:0', 'max:60'],
            'interne'            => ['sometimes', 'boolean'],
            'actif'              => ['sometimes', 'boolean'],
        ], [
            'date_naissance.before_or_equal' => 'تاريخ الازدياد لا يمكن أن يكون في المستقبل.',
        ]);
    }
}
