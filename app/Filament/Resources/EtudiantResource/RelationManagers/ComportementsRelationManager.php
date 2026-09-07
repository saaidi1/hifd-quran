<?php

namespace App\Filament\Resources\EtudiantResource\RelationManagers;

use App\Enums\TypeComportement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/** السلوك والملاحظات */
/**
 * Relation « comportements » : incidents et bons points de l'élève,
 * avec auteur de la remarque (signale_par) renseigné automatiquement.
 */
class ComportementsRelationManager extends RelationManager
{
    protected static string $relationship = 'comportements';
    protected static ?string $title = 'السلوك والملاحظات';

    /* Gravité et sanction demandées uniquement pour un comportement négatif. */
    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\DatePicker::make('date')->label('التاريخ')->default(now())->required()->maxDate(now()),
                Forms\Components\Select::make('type')->label('النوع')->options(TypeComportement::class)->required()->live(),
                Forms\Components\Select::make('categorie')->label('الصنف')->options([
                    'nadafa'   => 'النظافة',
                    'ihtiram'  => 'الاحترام',
                    'taakhkhur'=> 'التأخر',
                    'ghiyab'   => 'الغياب',
                    'shijar'   => 'الشجار',
                    'tafawwuq' => 'التفوق والاجتهاد',
                    'moussaada'=> 'المساعدة والتعاون',
                ])->searchable(),
                Forms\Components\Select::make('gravite')->label('درجة الخطورة')
                    ->options([1 => 'خفيفة', 2 => 'متوسطة', 3 => 'خطيرة'])
                    ->visible(fn (Forms\Get $get) => $get('type') === TypeComportement::NEGATIF->value)
                    ->required(fn (Forms\Get $get) => $get('type') === TypeComportement::NEGATIF->value),
                Forms\Components\TextInput::make('points')->label('النقط (+/-)')->numeric()->minValue(-10)->maxValue(10)->default(0),
            ]),
            Forms\Components\Textarea::make('description')->label('الوصف')->required()->rows(3)->columnSpanFull(),
            Forms\Components\Textarea::make('sanction')->label('الإجراء المتخذ')->rows(2)->columnSpanFull()
                ->visible(fn (Forms\Get $get) => $get('type') === TypeComportement::NEGATIF->value),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->label('التاريخ')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge(),
                Tables\Columns\TextColumn::make('categorie')->label('الصنف')->toggleable(),
                Tables\Columns\TextColumn::make('gravite')->label('الخطورة')
                    ->formatStateUsing(fn ($state) => [1 => 'خفيفة', 2 => 'متوسطة', 3 => 'خطيرة'][$state] ?? '—')
                    ->badge()->color(fn ($state) => [1 => 'gray', 2 => 'warning', 3 => 'danger'][$state] ?? 'gray'),
                Tables\Columns\TextColumn::make('points')->label('النقط')->badge()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('description')->label('الوصف')->wrap()->limit(70),
                Tables\Columns\TextColumn::make('signalePar.nom_ar')->label('المُبلِّغ')->toggleable(),
            ])
            ->headerActions([
                /* Création : l'auteur de la remarque est l'utilisateur connecté. */
                Tables\Actions\CreateAction::make()->label('تسجيل ملاحظة')
                    ->mutateFormDataUsing(fn (array $data) => $data + ['signale_par' => auth()->id()]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('تعديل'),
                /* Seul le directeur peut effacer un incident. */
                Tables\Actions\DeleteAction::make()->label('حذف')->visible(fn () => auth()->user()->estDirecteur()),
            ])
            ->defaultSort('date', 'desc');
    }
}
