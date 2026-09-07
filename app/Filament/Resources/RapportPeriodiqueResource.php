<?php

namespace App\Filament\Resources;

use App\Enums\TypeRapportPeriodique;
use App\Filament\Resources\RapportPeriodiqueResource\Pages;
use App\Models\RapportPeriodique;
use App\Services\RapportPeriodiqueService;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * التقارير الأسبوعية والشهرية — للقراءة فقط، تُولَّد آليا.
 *
 * Bilans hebdomadaires/mensuels générés par RapportPeriodiqueService depuis
 * les rapports quotidiens. Consultation réservée au directeur et au superviseur ;
 * aucune saisie manuelle (canCreate = false).
 */
class RapportPeriodiqueResource extends Resource
{
    protected static ?string $model = RapportPeriodique::class;

    protected static ?string $navigationIcon   = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationGroup  = 'التقارير';
    protected static ?string $navigationLabel  = 'التقارير الأسبوعية والشهرية';
    protected static ?string $modelLabel       = 'تقرير';
    protected static ?string $pluralModelLabel = 'التقارير الدورية';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge(),
                Tables\Columns\TextColumn::make('etudiant.nom_complet_ar')->label('الطالب')->searchable(['nom_ar', 'prenom_ar'])->weight('bold'),
                Tables\Columns\TextColumn::make('groupe.nom_ar')->label('المجموعة')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('date_debut')->label('من')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('date_fin')->label('إلى')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('taux_presence')->label('الحضور')
                    ->state(fn (RapportPeriodique $r) => $r->taux_presence === null ? '—' : $r->taux_presence.'%')
                    ->badge()
                    /* Seuils : ≥ 75 % vert, ≥ 50 % orange, sinon rouge. */
                    ->color(fn ($state) => ! is_numeric($state) ? 'gray'
                        : ((float) $state >= 75 ? 'success' : ((float) $state >= 50 ? 'warning' : 'danger'))),
                Tables\Columns\TextColumn::make('nb_absences')->label('الغياب')
                    ->badge()->color(fn ($state) => $state > 2 ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('total_pages_hifd')->label('أوجه الحفظ')->numeric(2),
                Tables\Columns\TextColumn::make('total_pages_murajaa')->label('أوجه المراجعة')->numeric(2),
                Tables\Columns\TextColumn::make('moyenne_generale')->label('المعدل العام')->numeric(2)->badge()
                    ->color(fn ($state) => $state >= 14 ? 'success' : ($state >= 10 ? 'warning' : 'danger'))
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->label('النوع')->options(TypeRapportPeriodique::class),
                Tables\Filters\SelectFilter::make('groupe_id')->label('المجموعة')->relationship('groupe', 'nom_ar'),
            ])
            ->headerActions([
                /* Génération en masse des bilans pour tous les élèves, à une date de référence. */
                Tables\Actions\Action::make('generer')
                    ->label('توليد التقارير')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn () => auth()->user()->estDirecteur() || auth()->user()->estSuperviseur())
                    ->form([
                        Forms\Components\Select::make('type')->label('نوع التقرير')
                            ->options(TypeRapportPeriodique::class)->required(),
                        Forms\Components\DatePicker::make('reference')->label('تاريخ مرجعي داخل الفترة')
                            ->default(now())->required(),
                    ])
                    ->action(function (array $data) {
                        $nombre = app(RapportPeriodiqueService::class)->genererPourTous(
                            TypeRapportPeriodique::from($data['type']),
                            \Carbon\Carbon::parse($data['reference'])
                        );

                        Notification::make()->success()->title("تم توليد $nombre تقرير")->send();
                    }),
            ])
            ->defaultSort('date_debut', 'desc');
    }

    /* Lecture seule : les bilans ne se créent que par la génération automatique. */
    public static function canCreate(): bool { return false; }

    /* Consultation réservée au directeur et au superviseur. */
    public static function canViewAny(): bool
    {
        return auth()->user()->estDirecteur() || auth()->user()->estSuperviseur();
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListRapportsPeriodiques::route('/')];
    }
}
