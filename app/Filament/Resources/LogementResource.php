<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LogementResource\Pages;
use App\Models\Chambre;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * الغرف والإيواء — gestion des chambres de l'internat.
 *
 * Réservé au garde (الحارس) et au directeur : suivi des lits occupés/libres,
 * chaque lit portant le numéro d'inscription (matricule) de l'élève qui y dort.
 */
class LogementResource extends Resource
{
    protected static ?string $model = Chambre::class;

    protected static ?string $navigationIcon   = 'heroicon-o-home-modern';
    protected static ?string $navigationGroup  = 'الإدارة';
    protected static ?string $navigationLabel  = 'الغرف والإيواء';
    protected static ?string $modelLabel       = 'غرفة';
    protected static ?string $pluralModelLabel = 'الغرف';

    public static function form(Form $form): Form
    {
        return $form->schema([
            /* Fiche chambre : localisation, capacité et responsable. */
            Forms\Components\Grid::make(3)->schema([
                Forms\Components\TextInput::make('numero')->label('رقم الغرفة')->required(),
                Forms\Components\TextInput::make('batiment')->label('الجناح'),
                Forms\Components\TextInput::make('etage')->label('الطابق'),
                Forms\Components\TextInput::make('capacite')->label('عدد الأسرة')->numeric()->minValue(1)->default(4)->required(),
                Forms\Components\Select::make('responsable_id')->label('المسؤول')
                    ->relationship('responsable', 'nom_ar')->searchable(),
                Forms\Components\Toggle::make('actif')->label('صالحة للاستعمال')->default(true),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero')->label('رقم الغرفة')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('batiment')->label('الجناح'),
                Tables\Columns\TextColumn::make('capacite')->label('عدد الأسرة'),
                /* Lits occupés = hébergements actifs de la chambre. */
                Tables\Columns\TextColumn::make('occupes')->label('الأسرة المشغولة')
                    ->state(fn (Chambre $c) => $c->hebergements()->count())->badge(),
                /* Liste des numéros de lits libres ; « ممتلئة » si la chambre est pleine. */
                Tables\Columns\TextColumn::make('libres')->label('الأسرة الشاغرة')
                    ->state(fn (Chambre $c) => implode('، ', $c->litsLibres()) ?: 'ممتلئة')
                    ->badge()->color(fn (Chambre $c) => count($c->litsLibres()) ? 'success' : 'danger'),
                /* Pensionnaires : « lit n° : nom de l'élève » pour chaque hébergement actif. */
                Tables\Columns\TextColumn::make('pensionnaires')->label('المقيمون')->wrap()
                    ->state(fn (Chambre $c) => $c->hebergements()->with('etudiant')->get()
                        ->map(fn ($h) => "سرير {$h->numero_lit} : {$h->etudiant->nom_complet_ar}")->implode(' • ')),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('تعديل')])
            ->defaultSort('numero');
    }

    /* Accès limité au garde (logistique) et au directeur. */
    public static function canViewAny(): bool
    {
        return auth()->user()->estGarde() || auth()->user()->estDirecteur();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListChambres::route('/'),
            'create' => Pages\CreateChambre::route('/create'),
            'edit'   => Pages\EditChambre::route('/{record}/edit'),
        ];
    }
}
