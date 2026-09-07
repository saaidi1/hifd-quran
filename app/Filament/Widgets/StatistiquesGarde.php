<?php

namespace App\Filament\Widgets;

use App\Enums\StatutInscription;
use App\Enums\StatutPresence;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Models\RapportJournalier;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Cartes de statistiques du الحارس العام (garde général) : files d'attente
 * de pré-inscriptions, étudiants validés sans groupe, occupation du
 * dortoir (lits libres) et absences du jour. Source : tables Etudiant,
 * Chambre et RapportJournalier.
 */
class StatistiquesGarde extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    /** Réservé au garde général. */
    public static function canView(): bool
    {
        return auth()->user()->estGarde();
    }

    /** Compte les dossiers en attente, lits libres et absences à traiter aujourd'hui. */
    protected function getStats(): array
    {
        $litsLibres = Chambre::where('actif', true)->get()->sum(fn (Chambre $c) => count($c->litsLibres()));

        return [
            Stat::make('تسجيلات مبدئية', Etudiant::where('statut', StatutInscription::PREINSCRIT)->count())
                ->description('في انتظار اختبار المشرف')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('مقبولون بدون مجموعة', Etudiant::valides()->sansGroupe()->count())
                ->description('في انتظار الإسناد')
                ->descriptionIcon('heroicon-m-arrow-right-circle')
                ->color('info'),

            Stat::make('المقيمون بالداخلية', Etudiant::valides()->internes()->count())
                ->description($litsLibres . ' سرير شاغر')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('success'),

            Stat::make('غيابات اليوم', RapportJournalier::whereDate('date', today())
                    ->where('presence', StatutPresence::ABSENT)->count())
                ->description('يجب إشعار أولياء الأمور')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
