<?php

namespace App\Http\Controllers;

use App\Enums\TypeRapportPeriodique;
use App\Exports\RapportsPeriodiquesExport;
use App\Models\Etudiant;
use App\Models\Groupe;
use App\Models\RapportPeriodique;
use App\Services\RapportPeriodiqueService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Rapports périodiques (hebdomadaire / mensuel) par étudiant : consultation,
 * détail et génération en masse via RapportPeriodiqueService.
 *
 * Consultables par tous les rôles ; la génération se fait depuis l'interface
 * direction/supervision. Les filtres portent sur le type, le groupe, le mois
 * de date_fin et une recherche sur l'étudiant.
 */
class RapportPeriodiqueController extends Controller
{
    /** Liste paginée des rapports périodiques avec filtres (type, groupe, mois, recherche étudiant) conservés dans les liens de pagination. */
    public function index(Request $request): View
    {
        $query = RapportPeriodique::query()
            ->with(['etudiant', 'groupe'])
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->when($request->filled('groupe_id'), fn ($q) => $q->where('groupe_id', $request->integer('groupe_id')))
            ->when($request->filled('mois'), function ($q) use ($request) {
                // « mois » au format Y-m : filtrage sur l'année et le mois de date_fin.
                $q->whereYear('date_fin', substr($request->input('mois'), 0, 4))
                  ->whereMonth('date_fin', substr($request->input('mois'), 5));
            })
            ->when($request->filled('q'), fn ($q) => $q->whereHas('etudiant',
                fn ($e) => $e->recherche($request->input('q'))))
            ->orderByDesc('date_fin')
            ->orderByDesc('id');

        return view('rapport-periodiques.index', [
            'rapports' => $query->paginate(25)->withQueryString(),
            'groupes'  => Groupe::query()->orderBy('nom_ar')->get(),
            'filtres'  => $request->only(['type', 'groupe_id', 'mois', 'q']),
        ]);
    }

    /** Détail d'un rapport périodique + toutes les séances journalières de l'étudiant sur la période couverte (pour justificatif à l'appui). */
    public function show(RapportPeriodique $rapport): View
    {
        $rapport->load(['etudiant', 'groupe', 'generePar']);

        $sessions = $rapport->etudiant?->rapportsJournaliers()
            ->with(['lignes.sourateDebut', 'lignes.sourateFin'])
            ->whereBetween('date', [$rapport->date_debut, $rapport->date_fin])
            ->orderBy('date')
            ->get();

        return view('rapport-periodiques.show', [
            'rapport'  => $rapport,
            'sessions' => $sessions ?? collect(),
        ]);
    }

    /** Télécharge le rapport périodique au format PDF (justificatif à imprimer). */
    public function telechargerPdf(RapportPeriodique $rapport)
    {
        $rapport->load(['etudiant', 'groupe', 'generePar']);

        $sessions = $rapport->etudiant?->rapportsJournaliers()
            ->with(['lignes.sourateDebut', 'lignes.sourateFin'])
            ->whereBetween('date', [$rapport->date_debut, $rapport->date_fin])
            ->orderBy('date')
            ->get();

        $pdf = Pdf::loadView('rapport-periodiques.pdf', [
            'rapport'  => $rapport,
            'sessions' => $sessions ?? collect(),
        ]);

        return $pdf->download('rapport-periodique-'.$rapport->date_fin->format('Y-m-d').'.pdf');
    }

    /** Exporte la liste filtrée courante des rapports périodiques au format Excel. */
    public function exporterExcel(Request $request)
    {
        $filtres = $request->only(['type', 'groupe_id', 'mois', 'q']);

        return Excel::download(new RapportsPeriodiquesExport($filtres), 'rapports-periodiques.xlsx');
    }

    /** Génère en masse un rapport périodique (type + mois) pour tous les étudiants valides affectés à un groupe, ou un seul groupe si précisé. Traite par lots de 200 pour limiter la mémoire. */
    public function generer(Request $request, RapportPeriodiqueService $service): RedirectResponse
    {
        $donnees = $request->validate([
            'type'   => ['required', Rule::enum(TypeRapportPeriodique::class)],
            'mois'   => ['required', 'date_format:Y-m'],
            'groupe' => ['nullable', 'exists:groupes,id'],
        ]);

        [$annee, $mois] = explode('-', $donnees['mois']);
        // Référence = 1er jour du mois choisi (base de calcul de la période).
        $reference = now()->setDate((int) $annee, (int) $mois, 1);

        $etudiants = Etudiant::valides()->whereNotNull('groupe_id')
            ->when($donnees['groupe'] ?? null, fn ($q, $g) => $q->where('groupe_id', $g));

        $compte = 0;
        // chunkById : évite de charger toute la table en mémoire ; compteur passé par référence.
        $etudiants->chunkById(200, function ($pagination) use ($service, $donnees, $reference, &$compte) {
            foreach ($pagination as $etudiant) {
                $service->generer($etudiant, TypeRapportPeriodique::from($donnees['type']), $reference, auth()->id());
                $compte++;
            }
        });

        return redirect()->route('rapports-periodiques.index', [
            'type' => $donnees['type'],
            'mois' => $donnees['mois'],
        ])->with('success', __(':count reports generated.', ['count' => $compte]));
    }
}
