<?php

namespace App\Filament\Resources\RapportPeriodiqueResource\Pages;

use App\Filament\Resources\RapportPeriodiqueResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste en lecture seule des bilans hebdomadaires et mensuels
 * (génération via l'action d'en-tête de la ressource).
 */
class ListRapportsPeriodiques extends ListRecords
{
    protected static string $resource = RapportPeriodiqueResource::class;

    protected static ?string $title = 'التقارير الأسبوعية والشهرية';
}
