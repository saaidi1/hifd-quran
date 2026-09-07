<?php

namespace App\Filament\Resources\ProfesseurResource\Pages;

use App\Filament\Resources\ProfesseurResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Modification d'un compte utilisateur (mot de passe optionnel, rôle modifiable).
 */
class EditProfesseur extends EditRecord
{
    protected static string $resource = ProfesseurResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()->label('حذف')];
    }
}
