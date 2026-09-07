<?php

namespace App\Filament\Resources\LogementResource\Pages;

use App\Filament\Resources\LogementResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Modification d'une chambre ; suppression possible si aucune hébergement n'y est rattaché.
 */
class EditChambre extends EditRecord
{
    protected static string $resource = LogementResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('حذف')];
    }
}
