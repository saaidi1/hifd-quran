<?php

namespace App\Filament\Resources\EtudiantResource\Pages;

use App\Enums\StatutInscription;
use App\Filament\Resources\EtudiantResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/**
 * Liste des élèves avec onglets par étape du workflow d'admission
 * et bouton de préinscription (selon la policy).
 */
class ListEtudiants extends ListRecords
{
    protected static string $resource = EtudiantResource::class;

    /* Bouton « تسجيل مبدئي جديد » affiché selon la policy Etudiant::create. */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('تسجيل مبدئي جديد')
                ->icon('heroicon-o-user-plus')
                ->visible(fn () => auth()->user()->can('create', \App\Models\Etudiant::class)),
        ];
    }

    /** Onglets correspondant aux étapes du workflow. */
    public function getTabs(): array
    {
        return [
            'الكل' => Tab::make(),

            'في انتظار الاختبار' => Tab::make()
                ->modifyQueryUsing(fn (Builder $q) => $q->where('statut', StatutInscription::PREINSCRIT))
                ->badge(fn () => \App\Models\Etudiant::where('statut', StatutInscription::PREINSCRIT)->count())
                ->badgeColor('warning'),

            'مقبولون بدون مجموعة' => Tab::make()
                ->modifyQueryUsing(fn (Builder $q) => $q->where('statut', StatutInscription::VALIDE)->whereNull('groupe_id'))
                ->badge(fn () => \App\Models\Etudiant::valides()->sansGroupe()->count())
                ->badgeColor('info'),

            'مسندون إلى مجموعات' => Tab::make()
                ->modifyQueryUsing(fn (Builder $q) => $q->where('statut', StatutInscription::VALIDE)->whereNotNull('groupe_id')),

            'مرفوضون' => Tab::make()
                ->modifyQueryUsing(fn (Builder $q) => $q->whereIn('statut', [StatutInscription::REFUSE, StatutInscription::AJOURNE])),
        ];
    }
}
