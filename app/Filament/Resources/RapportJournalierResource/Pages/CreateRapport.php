<?php

namespace App\Filament\Resources\RapportJournalierResource\Pages;

use App\Filament\Resources\RapportJournalierResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création manuelle d'un rapport quotidien par le professeur.
 */
class CreateRapport extends CreateRecord
{
    protected static string $resource = RapportJournalierResource::class;

    protected static ?string $title = 'تقرير يومي جديد';

    /**
     * Le professeur enregistre sous son nom ; les autres rôles
     * choisissent l'الأستاذ المشرف (sinon fallback sur le compte courant).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /* Le professeur enregistre sous son nom ; les autres rôles choisissent l'الأستاذ المشرف. */
        $data['professeur_id'] = auth()->user()->estProfesseur()
            ? auth()->id()
            : ($data['professeur_id'] ?? auth()->id());

        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم تسجيل التقرير اليومي';
    }
}
