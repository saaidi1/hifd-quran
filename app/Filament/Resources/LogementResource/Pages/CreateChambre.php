<?php

namespace App\Filament\Resources\LogementResource\Pages;

use App\Filament\Resources\LogementResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d'une chambre (garde / directeur uniquement).
 */
class CreateChambre extends CreateRecord
{
    protected static string $resource = LogementResource::class;
}
