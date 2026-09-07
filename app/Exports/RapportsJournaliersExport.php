<?php

namespace App\Exports;

use App\Models\RapportJournalier;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

/**
 * Export Excel de la liste des rapports journaliers avec les mêmes filtres
 * que la page d'index (dates, recherche étudiant, périmètre professeur).
 */
class RapportsJournaliersExport implements FromQuery, WithHeadings, WithMapping
{
    /** Filtres reçus depuis l'URL (q, debut, fin). */
    public function __construct(private array $filtres = [], private ?array $groupesArmature = null)
    {
    }

    /** Requête filtrée identique à l'index, sans pagination. $groupesArmature = groupe_ids autorisés pour un professeur (null = tous). */
    public function query(): Builder
    {
        $debut = $this->filtres['debut'] ?? now()->startOfMonth()->toDateString();
        $fin   = $this->filtres['fin']   ?? now()->endOfMonth()->toDateString();

        $query = RapportJournalier::query()
            ->with(['etudiant', 'groupe', 'professeur', 'lignes.sourateDebut', 'lignes.sourateFin'])
            ->whereBetween('date', [$debut, $fin]);

        if (! empty($this->filtres['q'] ?? '')) {
            $terme = trim((string) $this->filtres['q']);
            $query->whereHas('etudiant', fn (Builder $b) => $b->recherche($terme));
        }

        if ($this->groupesArmature !== null) {
            $query->whereIn('groupe_id', $this->groupesArmature);
        }

        return $query->orderByDesc('date')->orderByDesc('id');
    }

    /** En-têtes de colonnes (traduits : arabe dans l'application). */
    public function headings(): array
    {
        return [
            __('Date'),
            __('Student'),
            __('Halaqa'),
            __('Teacher'),
            __('Attendance'),
            __('Behavior'),
            __('Overall average'),
            __('Recited'),
        ];
    }

    /** Une ligne Excel = un rapport journalier. */
    public function map($rapport): array
    {
        $recite = $rapport->lignes->map(fn ($l) => sprintf(
            '%s: %d.%s → %d.%s',
            $l->type?->getLabel(),
            $l->sourateDebut?->numero,
            $l->ayah_debut,
            $l->sourateFin?->numero,
            $l->ayah_fin
        ))->implode(' | ');

        return [
            $rapport->date->translatedFormat('d/m/Y'),
            $rapport->etudiant?->nom_complet_ar,
            $rapport->groupe?->nom_ar,
            $rapport->professeur?->nom_ar,
            $rapport->presence?->getLabel(),
            $rapport->note_comportement?->getLabel(),
            $rapport->note_globale,
            $recite,
        ];
    }
}