<?php

namespace App\Filament\Widgets;

use App\Enums\StatutPresence;
use App\Models\RapportJournalier;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

/**
 * Graphique linéaire « الحضور خلال 30 يوما » : évolution quotidienne des
 * présents et des absents sur les 30 derniers jours, agrégée par SQL
 * depuis les rapports journaliers. Réservé au directeur et au superviseur.
 */
class CourbeAssiduite extends ChartWidget
{
    protected static ?string $heading = 'الحضور خلال 30 يوما';
    protected static ?int $sort = 2;

    /** Visible uniquement pour المدير et المشرف. */
    public static function canView(): bool
    {
        return auth()->user()->estDirecteur() || auth()->user()->estSuperviseur();
    }

    /** Comptage SQL des présences/absences par jour, mis en forme pour Chart.js. */
    protected function getData(): array
    {
        $donnees = RapportJournalier::query()
            ->where('date', '>=', now()->subDays(30))
            ->select('date',
                DB::raw("SUM(presence = '" . StatutPresence::PRESENT->value . "') as presents"),
                DB::raw("SUM(presence = '" . StatutPresence::ABSENT->value . "') as absents"))
            ->groupBy('date')->orderBy('date')->get();

        return [
            'datasets' => [
                [
                    'label' => 'الحاضرون',
                    'data'  => $donnees->pluck('presents')->all(),
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16,185,129,0.15)',
                    'fill' => true,
                ],
                [
                    'label' => 'الغائبون',
                    'data'  => $donnees->pluck('absents')->all(),
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239,68,68,0.15)',
                    'fill' => true,
                ],
            ],
            'labels' => $donnees->pluck('date')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d/m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
