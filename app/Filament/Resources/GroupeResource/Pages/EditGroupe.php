<?php

namespace App\Filament\Resources\GroupeResource\Pages;

use App\Filament\Resources\GroupeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Modification d'un groupe (le professeur n'accède qu'à ses groupes) ;
 * suppression affichée mais filtrée par la règle « groupe vide » de la table.
 */
class EditGroupe extends EditRecord
{
    protected static string $resource = GroupeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('حذف')];
    }
}
