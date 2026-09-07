<?php

namespace App\Filament\Resources\RapportJournalierResource\Pages;

use App\Filament\Resources\RapportJournalierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des rapports quotidiens (filtrée par rôle via getEloquentQuery) ;
 * la saisie manuelle reste l'apanage du professeur.
 */
class ListRapports extends ListRecords
{
    protected static string $resource = RapportJournalierResource::class;

    /* Bouton de création visible uniquement pour le professeur. */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('تسجيل تقرير يومي')
                ->visible(fn () => auth()->user()->estProfesseur()),
        ];
    }
}
