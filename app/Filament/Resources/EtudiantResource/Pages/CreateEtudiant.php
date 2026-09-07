<?php

namespace App\Filament\Resources\EtudiantResource\Pages;

use App\Filament\Resources\EtudiantResource;
use App\Services\InscriptionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

/**
 * Préinscription d'un nouvel élève : la création passe par InscriptionService
 * (matricule, statut « préinscrit » et traçabilité automatiques).
 */
class CreateEtudiant extends CreateRecord
{
    protected static string $resource = EtudiantResource::class;

    protected static ?string $title = 'تسجيل مبدئي لطالب جديد';

    /** La création passe par le service : matricule, statut et traçabilité automatiques. */
    protected function handleRecordCreation(array $data): Model
    {
        return app(InscriptionService::class)->preinscrire($data, auth()->user());
    }

    /* Message de confirmation rappelant l'étape suivante (test du superviseur). */
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم التسجيل المبدئي — في انتظار اختبار المشرف على الأساتذة';
    }

    /* Retour à la liste après création (pas sur la fiche créée). */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
