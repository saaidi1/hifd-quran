<?php

namespace App\Filament\Resources\RapportJournalierResource\Pages;

use App\Filament\Resources\RapportJournalierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

/**
 * Modification d'un rapport quotidien ; la moyenne globale est recalculée après sauvegarde.
 */
class EditRapport extends EditRecord
{
    protected static string $resource = RapportJournalierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            /* Suppression réservée au directeur. */
            Actions\DeleteAction::make()->label('حذف')->visible(fn () => auth()->user()->estDirecteur()),
        ];
    }

    /* Recalcul de note_globale après modification des lignes. */
    protected function afterSave(): void
    {
        $this->record->recalculerNoteGlobale();
    }
}
