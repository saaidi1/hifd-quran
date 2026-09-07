<?php

namespace App\Filament\Resources\EtudiantResource\Pages;

use App\Filament\Resources\EtudiantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Modification du dossier élève ; la suppression reste un privilège directeur.
 */
class EditEtudiant extends EditRecord
{
    protected static string $resource = EtudiantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('عرض'),
            /* Suppression réservée au directeur. */
            Actions\DeleteAction::make()->label('حذف')
                ->visible(fn () => auth()->user()->estDirecteur()),
        ];
    }
}
