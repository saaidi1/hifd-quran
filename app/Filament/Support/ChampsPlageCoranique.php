<?php

namespace App\Filament\Support;

use App\Models\Sourate;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;

/**
 * Champs réutilisables pour saisir « من الآية ... من سورة ... إلى الآية ... ».
 * Le nombre maximal de versets est borné dynamiquement par la sourate choisie.
 * Utilisé dans les formulaires de lignes de rapport (hifd jadid / qadim)
 * pour garantir des plages coraniques cohérentes.
 */
class ChampsPlageCoranique
{
    /**
     * Construit la grille de saisie d'une plage (sourate + verset de début et
     * de fin) : bornes par sourate, auto-complétion de la sourate de fin,
     * et validation que la fin ne précède pas le début.
     */
    public static function schema(): array
    {
        return [
            Grid::make(4)->schema([
                Select::make('sourate_debut_id')
                    ->label('من سورة')
                    ->options(fn () => Sourate::orderBy('numero')->pluck('nom_ar', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    // la plupart des portions restent dans la même sourate
                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                        if (blank($get('sourate_fin_id'))) {
                            $set('sourate_fin_id', $state);
                        }
                    }),

                TextInput::make('ayah_debut')
                    ->label('من الآية')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->maxValue(fn (Get $get) => Sourate::find($get('sourate_debut_id'))?->nb_ayat ?? 286)
                    ->helperText(fn (Get $get) => ($s = Sourate::find($get('sourate_debut_id')))
                        ? "عدد آيات السورة : {$s->nb_ayat}"
                        : null),

                Select::make('sourate_fin_id')
                    ->label('إلى سورة')
                    ->options(fn () => Sourate::orderBy('numero')->pluck('nom_ar', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),

                TextInput::make('ayah_fin')
                    ->label('إلى الآية')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->maxValue(fn (Get $get) => Sourate::find($get('sourate_fin_id'))?->nb_ayat ?? 286)
                    ->rules([
                        fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                            $debut = Sourate::find($get('sourate_debut_id'));
                            $fin   = Sourate::find($get('sourate_fin_id'));

                            if ($debut && $fin && $fin->positionGlobale((int) $value) < $debut->positionGlobale((int) $get('ayah_debut'))) {
                                $fail('نهاية المقطع لا يمكن أن تسبق بدايته.');
                            }
                        },
                    ]),
            ]),
        ];
    }

    /** Colonne de tableau affichant la plage en toutes lettres. */
    public static function libelle($record): string
    {
        return $record->libellePlage();
    }
}
