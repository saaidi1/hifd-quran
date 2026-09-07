<?php

namespace App\Exports;

use App\Models\RapportPeriodique;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export Excel de la liste des rapports périodiques avec les mêmes filtres
 * que la page d'index (type, groupe, mois, recherche étudiant).
 */
class RapportsPeriodiquesExport implements FromQuery, WithHeadings, WithMapping
{
    /** Filtres reçus depuis l'URL (type, groupe_id, mois, q). */
    public function __construct(private array $filtres = [])
    {
    }

    /** Requête filtrée identique à l'index, sans pagination. */
    public function query(): Builder
    {
        return RapportPeriodique::query()
            ->with(['etudiant', 'groupe'])
            ->when(! empty($this->filtres['type'] ?? ''), fn ($q) => $q->where('type', $this->filtres['type']))
            ->when(! empty($this->filtres['groupe_id'] ?? ''), fn ($q) => $q->where('groupe_id', (int) $this->filtres['groupe_id']))
            ->when(! empty($this->filtres['mois'] ?? ''), function ($q) {
                $mois = $this->filtres['mois'];

                return $q->whereYear('date_fin', substr($mois, 0, 4))
                    ->whereMonth('date_fin', substr($mois, 5));
            })
            ->when(! empty($this->filtres['q'] ?? ''), fn ($q) => $q->whereHas('etudiant',
                fn ($e) => $e->recherche($this->filtres['q'])))
            ->orderByDesc('date_fin')
            ->orderByDesc('id');
    }

    /** En-têtes de colonnes (traduits : arabe dans l'application). */
    public function headings(): array
    {
        return [
            __('Student'),
            __('Halaqa'),
            __('Type'),
            __('Period start'),
            __('Period end'),
            __('Sessions'),
            __('Presences'),
            __('Absences'),
            __('Lateness'),
            __('Attendance rate'),
            __('Memorized pages'),
            __('Revised pages'),
            __('Memorization level'),
            __('Revision level'),
            __('Behavior'),
            __('Overall appreciation'),
        ];
    }

    /** Une ligne Excel = un rapport périodique. */
    public function map($rapport): array
    {
        return [
            $rapport->etudiant?->nom_complet_ar,
            $rapport->groupe?->nom_ar,
            $rapport->type?->getLabel(),
            $rapport->date_debut->translatedFormat('d/m/Y'),
            $rapport->date_fin->translatedFormat('d/m/Y'),
            $rapport->nb_seances,
            $rapport->nb_presences,
            $rapport->nb_absences,
            $rapport->nb_retards,
            $rapport->taux_presence !== null ? $rapport->taux_presence / 100 : null,
            $rapport->total_pages_hifd,
            $rapport->total_pages_murajaa,
            $rapport->moyenne_hifd?->getLabel(),
            $rapport->moyenne_murajaa?->getLabel(),
            $rapport->moyenne_comportement?->getLabel(),
            $rapport->appreciation,
        ];
    }
}