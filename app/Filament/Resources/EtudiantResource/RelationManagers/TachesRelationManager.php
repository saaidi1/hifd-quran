<?php

namespace App\Filament\Resources\EtudiantResource\RelationManagers;

use App\Enums\TypeSeance;
use App\Filament\Support\ChampsPlageCoranique;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** الواجب المقرر : ما يجب على الطالب حفظه أو مراجعته. */
/**
 * Relation « taches » : devoirs de mémorisation/révision assignés à l'élève.
 * Création réservée au professeur (professeur_id et statut forcés à la création) ;
 * ces devoirs pré-remplissent ensuite la حصة et les rapports quotidiens.
 */
class TachesRelationManager extends RelationManager
{
    protected static string $relationship = 'taches';
    protected static ?string $title = 'المقرر (الحفظ والمراجعة)';

    /* Plage coranique (sourate/ayat) + échéance de récitation. */
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')->label('نوع المقرر')
                ->options(TypeSeance::class)->default(TypeSeance::HIFD_JADID->value)->required(),

            ...ChampsPlageCoranique::schema(),

            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('nb_pages')->label('عدد الأوجه')->numeric()->step(0.25)->minValue(0),
                Forms\Components\DatePicker::make('date_echeance')->label('تاريخ التسميع')
                    ->default(now()->addDay())->required()->minDate(now()),
            ]),

            Forms\Components\Textarea::make('consignes')->label('توجيهات الأستاذ')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge(),
                Tables\Columns\TextColumn::make('plage')->label('المقطع')->wrap()
                    ->state(fn ($record) => $record->libellePlage()),
                Tables\Columns\TextColumn::make('versets')->label('عدد الآيات')
                    ->state(fn ($record) => $record->nombreVersets()),
                Tables\Columns\TextColumn::make('nb_pages')->label('الأوجه')->numeric(2),
                Tables\Columns\TextColumn::make('date_echeance')->label('تاريخ التسميع')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('statut')->label('الحالة')->badge()
                    ->formatStateUsing(fn ($state) => [
                        'assignee' => 'مقرر', 'realisee' => 'تم', 'partielle' => 'جزئي', 'non_realisee' => 'لم ينجز',
                    ][$state] ?? $state)
                    ->color(fn ($state) => [
                        'assignee' => 'info', 'realisee' => 'success', 'partielle' => 'warning', 'non_realisee' => 'danger',
                    ][$state] ?? 'gray'),
            ])
            ->headerActions([
                /* Création réservée au professeur : auteur, date et statut « assignee » forcés. */
                Tables\Actions\CreateAction::make()->label('تحديد مقطع جديد')
                    ->visible(fn () => auth()->user()->estProfesseur())
                    ->mutateFormDataUsing(fn (array $data) => $data + [
                        'professeur_id'    => auth()->id(),
                        'date_assignation' => now()->toDateString(),
                        'statut'           => 'assignee',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                Tables\Actions\DeleteAction::make()->label('حذف'),
            ])
            ->defaultSort('date_echeance', 'desc');
    }
}
