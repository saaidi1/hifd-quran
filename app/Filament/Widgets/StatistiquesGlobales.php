<?php

namespace App\Filament\Widgets;

use App\Enums\StatutInscription;
use App\Enums\StatutPresence;
use App\Models\Etudiant;
use App\Models\Groupe;
use App\Models\RapportJournalier;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Cartes de pilotage du المدير et du المشرف : effectifs validés, dossiers
 * en attente de test, groupes actifs, assiduité du jour et moyenne du mois.
 * Données issues des tables Etudiant, Groupe, User et RapportJournalier.
 */
class StatistiquesGlobales extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    /** Visible pour le directeur et le superviseur (pas les professeurs ni le garde). */
    public static function canView(): bool
    {
        return auth()->user()->estDirecteur() || auth()->user()->estSuperviseur();
    }

    /** Agrège effectifs, présences du jour et moyenne mensuelle en cartes colorées. */
    protected function getStats(): array
    {
        $aujourdhui = RapportJournalier::whereDate('date', today());
        $presents   = (clone $aujourdhui)->where('presence', StatutPresence::PRESENT)->count();
        $absents    = (clone $aujourdhui)->where('presence', StatutPresence::ABSENT)->count();
        $moyenne    = RapportJournalier::where('date', '>=', now()->startOfMonth())->avg('note_globale');

        return [
            Stat::make('الطلبة المقبولون', Etudiant::valides()->count())
                ->description(Etudiant::valides()->sansGroupe()->count() . ' بدون مجموعة')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('في انتظار الاختبار', Etudiant::where('statut', StatutInscription::PREINSCRIT)->count())
                ->description('ملفات تنتظر قرار المشرف')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('المجموعات', Groupe::where('actif', true)->count())
                ->description(User::professeurs()->where('actif', true)->count() . ' أستاذ')
                ->descriptionIcon('heroicon-m-rectangle-group')
                ->color('info'),

            Stat::make('حضور اليوم', $presents)
                ->description($absents . ' غياب')
                ->descriptionIcon($absents > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($absents > 5 ? 'danger' : 'success'),

            Stat::make('معدل الشهر', $moyenne ? number_format($moyenne, 2) . ' / 20' : '—')
                ->description('معدل جميع التقارير اليومية')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($moyenne >= 14 ? 'success' : ($moyenne >= 10 ? 'warning' : 'danger')),
        ];
    }
}
