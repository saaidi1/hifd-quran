<?php

namespace App\Http\Controllers;

use App\Enums\StatutInscription;
use App\Enums\StatutPresence;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Models\Groupe;
use App\Models\RapportJournalier;
use App\Models\TacheMemorisation;
use App\Models\User;
use App\Services\ProgressionService;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Tableau de bord principal (route GET /) : tableau de bord détaillé et
 * différent selon le rôle connecté (directeur, superviseur, professeur, garde).
 * Chaque vue de rôle reçoit uniquement les données qui la concernent.
 */
class DashboardController extends Controller
{
    /** Construit les données du tableau de bord selon le rôle de l'utilisateur connecté. */
    public function index(ProgressionService $progression): View
    {
        $user = auth()->user();

        $donnees = [
            'stats' => $this->statsPour($user),
            'user'  => $user,
        ];

        if ($user->estDirecteur()) {
            return view('dashboard.index', $donnees + [
                'assiduite'     => $this->assiduite30Jours(),
                'progression'   => $this->topProgression($progression),
                'parHalaqa'     => $this->syntheseParHalaqa(),
                'derniersRapports' => $this->derniersRapports(8),
                'statuts'       => $this->effectifsParStatut(),
            ]);
        }

        if ($user->estSuperviseur()) {
            return view('dashboard.index', $donnees + [
                'assiduite'       => $this->assiduite30Jours(),
                'progression'     => $this->topProgression($progression),
                'parHalaqa'       => $this->syntheseParHalaqa(),
                'evaluations'     => $this->evaluationsEnAttente(10),
                'encadrants'      => $this->encadrants(),
            ]);
        }

        if ($user->estGarde()) {
            return view('dashboard.index', $donnees + [
                'litsParChambre'  => $this->litsParChambre(),
                'internes'        => $this->internes(12),
                'preinscriptions' => $this->preinscriptions(8),
                'absencesJour'    => $this->absencesDuJour(),
                'aAffecter'       => Etudiant::valides()->sansGroupe()->with('groupe')->orderBy('nom_ar')->get(),
            ]);
        }

        // Professeur : périmètre limité à SES groupes et SES étudiants.
        return view('dashboard.index', $donnees + [
            'mesGroupes'      => $this->syntheseParHalaqa(auth()->user()->groupes()->where('actif', true)),
            'aCompleter'      => $this->aCompleterAujourdHui($user),
            'apercuEtudiants' => $this->apercuEtudiants($user, $progression),
        ]);
    }

    /** Top 15 des étudiants valides par pourcentage de mémorisation (avec moyenne mensuelle). */
    private function topProgression(ProgressionService $progression)
    {
        return Etudiant::valides()
            ->with('groupe')
            ->get()
            ->map(function (Etudiant $e) use ($progression) {
                $moyenne = $e->rapportsJournaliers()
                    ->where('date', '>=', now()->startOfMonth())
                    ->avg('note_globale');

                return (object) [
                    'id'          => $e->id,
                    'nom'         => $e->nom_complet_ar,
                    'groupe'      => $e->groupe?->nom_ar,
                    'versets'     => number_format($progression->versetsMemorises($e)),
                    'pourcentage' => $progression->pourcentage($e),
                    'rythme'      => $progression->rythmeHebdomadaire($e),
                    'moyenne'     => $moyenne ? number_format($moyenne, 2) : null,
                ];
            })
            ->sortByDesc('pourcentage')
            ->take(15);
    }

    /** Statistiques générales affichées selon le rôle (directeur/superviseur, garde, ou professeur). */
    private function statsPour(User $user): array
    {
        if ($user->estDirecteur() || $user->estSuperviseur()) {
            $aujourdhui = RapportJournalier::whereDate('date', today());
            $presents   = (clone $aujourdhui)->where('presence', StatutPresence::PRESENT)->count();
            $absents    = (clone $aujourdhui)->where('presence', StatutPresence::ABSENT)->count();
            $moyenne    = RapportJournalier::where('date', '>=', now()->startOfMonth())->avg('note_globale');

            return [
                ['icon' => 'bi-people-fill', 'icon_bg' => 'text-bg-success', 'label' => __('Accepted students'),
                 'value' => Etudiant::valides()->count(),
                 'description' => __(':count without a halaqa', ['count' => Etudiant::valides()->sansGroupe()->count()])],
                ['icon' => 'bi-hourglass-split', 'icon_bg' => 'text-bg-warning', 'label' => __('Awaiting test'),
                 'value' => Etudiant::where('statut', StatutInscription::PREINSCRIT)->count(),
                 'description' => __('Files awaiting supervisor decision')],
                ['icon' => 'bi-diagram-3-fill', 'icon_bg' => 'text-bg-info', 'label' => __('Halaqas'),
                 'value' => Groupe::where('actif', true)->count(),
                 'description' => __(':count teachers', ['count' => User::professeurs()->where('actif', true)->count()])],
                ['icon' => 'bi-calendar-check-fill', 'icon_bg' => $absents > 5 ? 'text-bg-danger' : 'text-bg-primary',
                 'label' => __('Today\'s attendance'), 'value' => $presents,
                 'description' => __(':count absences', ['count' => $absents])],
                ['icon' => 'bi-graph-up-arrow', 'icon_bg' => $moyenne >= 14 ? 'text-bg-success' : ($moyenne >= 10 ? 'text-bg-warning' : 'text-bg-danger'),
                 'label' => __('Monthly average'), 'value' => $moyenne ? number_format($moyenne, 2) . ' / 20' : '—',
                 'description' => __('Average of all daily reports')],
            ];
        }

        if ($user->estGarde()) {
            $litsLibres = Chambre::where('actif', true)->get()->sum(fn (Chambre $c) => count($c->litsLibres()));

            return [
                ['icon' => 'bi-hourglass-split', 'icon_bg' => 'text-bg-warning', 'label' => __('Preliminary registrations'),
                 'value' => Etudiant::where('statut', StatutInscription::PREINSCRIT)->count(),
                 'description' => __('Awaiting supervisor test')],
                ['icon' => 'bi-arrow-right-circle-fill', 'icon_bg' => 'text-bg-info', 'label' => __('Accepted without halaqa'),
                 'value' => Etudiant::valides()->sansGroupe()->count(),
                 'description' => __('Awaiting assignment')],
                ['icon' => 'bi-house-door-fill', 'icon_bg' => 'text-bg-success', 'label' => __('Boarding students'),
                 'value' => Etudiant::valides()->internes()->count(),
                 'description' => __(':count beds available', ['count' => $litsLibres])],
                ['icon' => 'bi-exclamation-triangle-fill', 'icon_bg' => 'text-bg-danger', 'label' => __('Today\'s absences'),
                 'value' => $this->absencesDuJour()->count(),
                 'description' => __('Parents must be notified')],
            ];
        }

        // Professeur : périmètre limité à SES groupes et SES saisies du jour.
        $groupes  = $user->groupes()->withCount(['etudiants' => fn ($q) => $q->where('statut', StatutInscription::VALIDE)])->get();
        $attendus = $groupes->sum('etudiants_count');
        $saisis   = RapportJournalier::where('professeur_id', $user->id)->whereDate('date', today())->count();
        $complet  = $saisis >= $attendus && $attendus > 0;

        return [
            ['icon' => 'bi-diagram-3-fill', 'icon_bg' => 'text-bg-info', 'label' => __('My halaqas'),
             'value' => $groupes->count(),
             'description' => __(':count students', ['count' => $attendus])],
            ['icon' => $complet ? 'bi-check-circle-fill' : 'bi-pencil-square', 'icon_bg' => $complet ? 'text-bg-success' : 'text-bg-warning',
             'label' => __('Today\'s reports'), 'value' => $saisis . ' / ' . $attendus,
             'description' => $complet ? __('Recording complete for today') : __(':count students remaining', ['count' => max(0, $attendus - $saisis)])],
            ['icon' => 'bi-book-half', 'icon_bg' => 'text-bg-primary', 'label' => __('Current assignments'),
             'value' => TacheMemorisation::where('professeur_id', $user->id)->enCours()->count(),
             'description' => __('Assignments awaiting recitation')],
        ];
    }

    /** Synthèse par حلقة : effectifs valides, capacité, présence du jour et moyenne mensuelle. */
    private function syntheseParHalaqa($groupes = null)
    {
        $auj = RapportJournalier::whereDate('date', today())
            ->select('groupe_id', DB::raw('COUNT(*) as total'),
                DB::raw("SUM(presence = '" . StatutPresence::PRESENT->value . "') as presents"),
                DB::raw("SUM(presence = '" . StatutPresence::ABSENT->value . "') as absents"))
            ->groupBy('groupe_id')->get()->keyBy('groupe_id');

        $moyennes = RapportJournalier::where('date', '>=', now()->startOfMonth())
            ->select('groupe_id', DB::raw('AVG(note_globale) as moyenne'))
            ->groupBy('groupe_id')->get()->keyBy('groupe_id');

        $groupes = $groupes ?? Groupe::query()->where('actif', true);

        return $groupes->with(['professeur'])
            ->withCount(['etudiants' => fn ($q) => $q->where('statut', StatutInscription::VALIDE)])
            ->orderBy('nom_ar')->get()
            ->map(function (Groupe $g) use ($auj, $moyennes) {
                $j = $auj[$g->id] ?? null;
                $m = $moyennes[$g->id] ?? null;

                return (object) [
                    'groupe'      => $g->nom_ar,
                    'professeur'  => $g->professeur?->nom_ar,
                    'etudiants'   => (int) $g->etudiants_count,
                    'capacite'    => $g->capacite,
                    'presents'    => (int) ($j->presents ?? 0),
                    'absents'     => (int) ($j->absents ?? 0),
                    'rapports'    => (int) ($j->total ?? 0),
                    'moyenneMois' => $m && $m->moyenne !== null ? round((float) $m->moyenne, 2) : null,
                ];
            });
    }

    /** Derniers rapports journaliers (globaux ou limités à certains groupes). */
    private function derniersRapports(int $nb, $groupesIds = null)
    {
        return RapportJournalier::with(['etudiant', 'groupe', 'professeur'])
            ->when($groupesIds, fn ($q) => $q->whereIn('groupe_id', $groupesIds))
            ->orderByDesc('date')->orderByDesc('id')->limit($nb)->get();
    }

    /** Effectifs par statut d'inscription (tout temps) pour le directeur. */
    private function effectifsParStatut(): array
    {
        return Etudiant::selectRaw('statut, COUNT(*) as total')
            ->groupBy('statut')->get()
            ->map(fn ($r) => (object) [
                'statut' => $r->statut instanceof StatutInscription ? $r->statut->getLabel() : (StatutInscription::tryFrom((string) $r->statut)?->getLabel() ?? '—'),
                'total'  => (int) $r->total,
            ])
            ->values()->all();
    }

    /** Étudiants pré-inscrits/en test attendant la décision du superviseur. */
    private function evaluationsEnAttente(int $nb)
    {
        return Etudiant::enAttente()->with(['preinscritPar', 'groupe'])
            ->orderByDesc('created_at')->limit($nb)->get();
    }

    /** Superviseurs et professeurs actifs : leurs حلقةs et effectifs. */
    private function encadrants()
    {
        return User::encadrants()->where('actif', true)->get()->map(function (User $u) {
            return (object) [
                'nom'       => $u->nom_ar ?: $u->nom_complet,
                'role'      => $u->role->getLabel(),
                'groupes'   => $u->groupes()->where('actif', true)->count(),
                'etudiants' => Etudiant::valides()->whereIn('groupe_id', $u->groupes()->select('id'))->count(),
            ];
        });
    }

    /** Occupation des lits par chambre active (occupés / libres). */
    private function litsParChambre()
    {
        return Chambre::where('actif', true)->orderBy('numero')->get()->map(function (Chambre $c) {
            $occupes = count($c->hebergements);

            return (object) [
                'numero'   => $c->numero,
                'batiment' => $c->batiment,
                'capacite' => $c->capacite,
                'occupes'  => $occupes,
                'libres'   => count($c->litsLibres()),
            ];
        });
    }

    /** Internes (hébergés) avec lit et chambre. */
    private function internes(int $nb)
    {
        return Etudiant::valides()->internes()->with(['hebergement.chambre', 'groupe'])
            ->orderBy('nom_ar')->limit($nb)->get();
    }

    /** Dernières pré-inscriptions avec leur auteur. */
    private function preinscriptions(int $nb)
    {
        return Etudiant::where('statut', StatutInscription::PREINSCRIT)
            ->with('preinscritPar')->orderByDesc('created_at')->limit($nb)->get();
    }

    /** Absences enregistrées aujourd'hui (étudiant + حلقة). */
    private function absencesDuJour()
    {
        return RapportJournalier::with(['etudiant', 'groupe'])
            ->whereDate('date', today())
            ->where('presence', StatutPresence::ABSENT)
            ->orderByDesc('id')->get();
    }

    /** Étudiants valides de SES groupes sans rapport enregistré aujourd'hui. */
    private function aCompleterAujourdHui(User $user)
    {
        $groupeIds    = $user->groupes()->pluck('id');
        $rapportesIds = RapportJournalier::whereDate('date', today())
            ->whereIn('groupe_id', $groupeIds)->pluck('etudiant_id');

        return Etudiant::valides()->with('groupe')->whereIn('groupe_id', $groupeIds)
            ->whereNotIn('id', $rapportesIds)->orderBy('nom_ar')->get();
    }

    /** Aperçu de SES étudiants : moyenne du mois et rythme hebdomadaire. */
    private function apercuEtudiants(User $user, ProgressionService $progression)
    {
        $groupeIds = $user->groupes()->pluck('id');
        $moyennes  = RapportJournalier::where('date', '>=', now()->startOfMonth())
            ->whereIn('groupe_id', $groupeIds)
            ->select('etudiant_id', DB::raw('AVG(note_globale) as moyenne'), DB::raw('COUNT(*) as nb'))
            ->groupBy('etudiant_id')->get()->keyBy('etudiant_id');

        return Etudiant::valides()->with('groupe')->whereIn('groupe_id', $groupeIds)
            ->orderBy('nom_ar')->get()
            ->map(function (Etudiant $e) use ($moyennes, $progression) {
                $m = $moyennes[$e->id] ?? null;

                return (object) [
                    'nom'     => $e->nom_complet_ar,
                    'groupe'  => $e->groupe?->nom_ar,
                    'moyenne' => $m && $m->moyenne !== null ? round((float) $m->moyenne, 2) : null,
                    'nb'      => (int) ($m->nb ?? 0),
                    'rythme'  => $progression->rythmeHebdomadaire($e),
                ];
            });
    }

    /** Présences / absences sur les 30 derniers jours (données pour le graphique). */
    private function assiduite30Jours(): array
    {
        $donnees = RapportJournalier::query()
            ->where('date', '>=', now()->subDays(30))
            ->select('date',
                DB::raw("SUM(presence = '" . StatutPresence::PRESENT->value . "') as presents"),
                DB::raw("SUM(presence = '" . StatutPresence::ABSENT->value . "') as absents"))
            ->groupBy('date')->orderBy('date')->get();

        return [
            'labels'    => $donnees->pluck('date')->map(fn ($d) => $d->format('d/m'))->all(),
            'presents'  => $donnees->pluck('presents')->all(),
            'absents'   => $donnees->pluck('absents')->all(),
        ];
    }
}