<?php

namespace App\Filament\Resources\ProfesseurResource\Pages;

use App\Filament\Resources\ProfesseurResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des comptes (professeurs et cadres) ; accès directeur uniquement.
 */
class ListProfesseurs extends ListRecords
{
    protected static string $resource = ProfesseurResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('إضافة أستاذ')];
    }
}
