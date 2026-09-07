<?php

namespace App\Filament\Resources\ProfesseurResource\Pages;

use App\Filament\Resources\ProfesseurResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d'un compte professeur/cadre par le directeur.
 */
class CreateProfesseur extends CreateRecord
{
    protected static string $resource = ProfesseurResource::class;
}
