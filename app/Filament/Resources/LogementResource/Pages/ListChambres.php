<?php

namespace App\Filament\Resources\LogementResource\Pages;

use App\Filament\Resources\LogementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des chambres de l'internat avec suivi des lits libres/occupés.
 */
class ListChambres extends ListRecords
{
    protected static string $resource = LogementResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة غرفة')];
    }
}
