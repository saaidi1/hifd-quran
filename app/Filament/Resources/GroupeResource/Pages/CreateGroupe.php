<?php

namespace App\Filament\Resources\GroupeResource\Pages;

use App\Filament\Resources\GroupeResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d'un groupe — réservée au directeur (voir GroupeResource::canCreate()).
 */
class CreateGroupe extends CreateRecord
{
    protected static string $resource = GroupeResource::class;
}
