<?php

namespace App\Filament\Resources\EtudiantResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Relation « evaluations » : historique des tests d'admission (اختبارات القبول)
 * passés par l'élève devant le superviseur. Consultation seule.
 */
class EvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluations';
    protected static ?string $title = 'اختبارات القبول';

    /* Tableau en lecture seule : notes par critère et décision finale. */
    public function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('date_test')->label('التاريخ')->date('d/m/Y'),
            Tables\Columns\TextColumn::make('superviseur.nom_ar')->label('المشرف'),
            Tables\Columns\TextColumn::make('hizb_maitrise')->label('المحفوظ (حزب)')->numeric(2),
            Tables\Columns\TextColumn::make('note_hifd')->label('الحفظ')->numeric(2),
            Tables\Columns\TextColumn::make('note_tajwid')->label('التجويد')->numeric(2),
            Tables\Columns\TextColumn::make('moyenne')->label('المعدل')->badge()
                ->color(fn ($state) => $state >= 14 ? 'success' : ($state >= 10 ? 'warning' : 'danger')),
            Tables\Columns\TextColumn::make('decision')->label('القرار')->badge()
                ->formatStateUsing(fn ($state) => ['valide' => 'قبول', 'refuse' => 'رفض', 'ajourne' => 'تأجيل'][$state] ?? $state)
                ->color(fn ($state) => ['valide' => 'success', 'refuse' => 'danger', 'ajourne' => 'gray'][$state] ?? 'gray'),
            Tables\Columns\TextColumn::make('observations')->label('ملاحظات')->wrap()->limit(60),
        ])->defaultSort('date_test', 'desc');
    }
}
