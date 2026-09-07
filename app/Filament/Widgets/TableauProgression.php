<?php

namespace App\Filament\Widgets;

use App\Models\Etudiant;
use App\Services\ProgressionService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tableau « تقدم الطلبة في الحفظ » : pour chaque étudiant validé, versets
 * mémorisés, % de khatma, rythme journalier (pages/vers) et moyenne du mois,
 * calculés par ProgressionService. Les professeurs ne voient que leurs
 * groupes ; directeur et superviseur voient toute l'école.
 */
class TableauProgression extends TableWidget
{
    protected static ?string $heading = 'تقدم الطلبة في الحفظ';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 'full';

    /** Construit la table : requête filtrée par rôle + colonnes de progression calculées à la volée. */
    public function table(Table $table): Table
    {
        $progression = app(ProgressionService::class);

        return $table
            ->query(fn (): Builder => Etudiant::valides()
                ->with('groupe')
                ->when(auth()->user()->estProfesseur(),
                    fn ($q) => $q->whereIn('groupe_id', auth()->user()->groupes()->select('id'))))
            ->columns([
                Tables\Columns\TextColumn::make('nom_complet_ar')->label('الطالب')->searchable(['nom_ar', 'prenom_ar']),
                Tables\Columns\TextColumn::make('groupe.nom_ar')->label('المجموعة')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('versets')->label('الآيات المحفوظة')
                    ->state(fn (Etudiant $e) => number_format($progression->versetsMemorises($e))),
                Tables\Columns\TextColumn::make('pourcentage')->label('نسبة الختمة')
                    ->state(fn (Etudiant $e) => $progression->pourcentage($e) . ' %')
                    ->badge()
                    ->color(fn (Etudiant $e) => match (true) {
                        $progression->pourcentage($e) >= 50 => 'success',
                        $progression->pourcentage($e) >= 20 => 'info',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('rythme')->label('المعدل اليومي (وجه)')
                    ->state(fn (Etudiant $e) => $progression->rythmeHebdomadaire($e)),
                Tables\Columns\TextColumn::make('moyenne')->label('معدل الشهر')
                    ->state(fn (Etudiant $e) => ($m = $e->rapportsJournaliers()
                        ->where('date', '>=', now()->startOfMonth())->avg('note_globale'))
                        ? number_format($m, 2) : '—')
                    ->badge()
                    ->color(fn ($state) => $state === '—' ? 'gray' : ($state >= 14 ? 'success' : ($state >= 10 ? 'warning' : 'danger'))),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('groupe_id')->label('المجموعة')->relationship('groupe', 'nom_ar'),
            ])
            ->paginated([10, 25, 50]);
    }
}
