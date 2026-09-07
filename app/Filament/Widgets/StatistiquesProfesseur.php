<?php

namespace App\Filament\Widgets;

use App\Models\RapportJournalier;
use App\Models\TacheMemorisation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Cartes de suivi du professeur (الأستاذ) : ses groupes (مجموعاتي) et
 * l'avancement de sa saisie du jour (rapports journaliers saisis vs
 * étudiants attendus), plus les tâches de mémorisation en cours.
 */
class StatistiquesProfesseur extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    /** Réservé aux comptes à rôle professeur (le superviseur inclus via estProfesseur). */
    public static function canView(): bool
    {
        return auth()->user()->estProfesseur();
    }

    /** Compare rapports saisis aujourd'hui et effectifs attendus par groupe. */
    protected function getStats(): array
    {
        $user    = auth()->user();
        $groupes = $user->groupes()->withCount('etudiants')->get();
        $attendus = $groupes->sum('etudiants_count');
        $saisis   = RapportJournalier::where('professeur_id', $user->id)->whereDate('date', today())->count();

        return [
            Stat::make('مجموعاتي', $groupes->count())
                ->description($attendus . ' طالب')
                ->descriptionIcon('heroicon-m-rectangle-group')
                ->color('info'),

            Stat::make('تقارير اليوم', $saisis . ' / ' . $attendus)
                ->description($saisis >= $attendus && $attendus > 0 ? 'اكتمل تسجيل اليوم' : 'يبقى ' . max(0, $attendus - $saisis) . ' طالب')
                ->descriptionIcon($saisis >= $attendus && $attendus > 0 ? 'heroicon-m-check-circle' : 'heroicon-m-pencil-square')
                ->color($saisis >= $attendus && $attendus > 0 ? 'success' : 'warning'),

            Stat::make('المقررات الجارية', TacheMemorisation::where('professeur_id', $user->id)->enCours()->count())
                ->description('واجبات في انتظار التسميع')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('primary'),
        ];
    }
}
