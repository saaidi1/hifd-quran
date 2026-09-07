<?php

namespace App\Filament\Resources\EtudiantResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Relation « rapportsJournaliers » : historique des rapports quotidiens
 * de l'élève (présence, passages récités, notes).
 */
class RapportsRelationManager extends RelationManager
{
    protected static string $relationship = 'rapportsJournaliers';
    protected static ?string $title = 'التقارير اليومية';

    /* Résumé des passages récités + filtre par intervalle de dates. */
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->label('التاريخ')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('presence')->label('الحضور')->badge(),
                Tables\Columns\TextColumn::make('lignes_resume')->label('المقاطع المسمعة')->wrap()
                    ->state(fn ($record) => $record->lignes
                        ->map(fn ($l) => $l->type->getLabel() . ' : ' . $l->libellePlage() . ' (' . $l->note . '/20)')
                        ->implode(' | ')),
                Tables\Columns\TextColumn::make('note_globale')->label('معدل اليوم')->numeric(2)->badge()
                    ->color(fn ($state) => $state >= 14 ? 'success' : ($state >= 10 ? 'warning' : 'danger')),
                Tables\Columns\TextColumn::make('note_comportement')->label('السلوك')->numeric(2),
                Tables\Columns\TextColumn::make('professeur.nom_ar')->label('الأستاذ')->toggleable(),
                Tables\Columns\TextColumn::make('remarques')->label('ملاحظات')->wrap()->limit(50)->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('periode')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('du')->label('من'),
                        \Filament\Forms\Components\DatePicker::make('au')->label('إلى'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['du'], fn ($q, $d) => $q->whereDate('date', '>=', $d))
                        ->when($data['au'], fn ($q, $d) => $q->whereDate('date', '<=', $d))),
            ])
            ->defaultSort('date', 'desc');
    }
}
