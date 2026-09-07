<?php

namespace App\Filament\Resources\EtudiantResource\Pages;

use App\Filament\Resources\EtudiantResource;
use App\Services\ProgressionService;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

/** الملف الكامل للطالب : الحفظ، السلوك، الإيواء، مكان الصلاة. */
/**
 * Fiche de consultation complète : progression calculée par ProgressionService,
 * documents, dernier test d'admission et synthèse comportementale.
 */
class ViewEtudiant extends ViewRecord
{
    protected static string $resource = EtudiantResource::class;

    /* Infolist en lecture seule : identité, progression, logistique, documents, comportement. */
    public function infolist(Infolist $infolist): Infolist
    {
        $progression = app(ProgressionService::class);

        return $infolist->schema([
            Infolists\Components\Section::make('البطاقة الشخصية')->columns(4)->schema([
                Infolists\Components\ImageEntry::make('photo')->label('')->circular(),
                Infolists\Components\TextEntry::make('matricule')->label('رقم التسجيل')->copyable(),
                Infolists\Components\TextEntry::make('nom_complet_ar')->label('الاسم الكامل')->weight('bold')->size('lg'),
                Infolists\Components\TextEntry::make('statut')->label('الحالة')->badge(),
                Infolists\Components\TextEntry::make('date_naissance')->label('تاريخ الازدياد')->date('d/m/Y'),
                Infolists\Components\TextEntry::make('tuteur_nom')->label('ولي الأمر'),
                Infolists\Components\TextEntry::make('tuteur_telephone')->label('هاتف ولي الأمر')->copyable(),
                Infolists\Components\TextEntry::make('groupe.nom_ar')->label('المجموعة')->badge(),
            ]),

            Infolists\Components\Section::make('تقدم الحفظ')->columns(4)->schema([
                Infolists\Components\TextEntry::make('versets')->label('الآيات المحفوظة')
                    ->state(fn ($record) => number_format($progression->versetsMemorises($record)) . ' / 6236'),
                Infolists\Components\TextEntry::make('pourcentage')->label('النسبة المئوية')
                    ->state(fn ($record) => $progression->pourcentage($record) . ' %')
                    ->badge()
                    ->color(fn ($record) => match (true) {
                        $progression->pourcentage($record) >= 50 => 'success',
                        $progression->pourcentage($record) >= 20 => 'info',
                        default => 'warning',
                    }),
                Infolists\Components\TextEntry::make('rythme')->label('المعدل الأسبوعي')
                    ->state(fn ($record) => $progression->rythmeHebdomadaire($record) . ' وجه / يوم'),
                Infolists\Components\TextEntry::make('hifd_initial_hizb')->label('المحفوظ عند التسجيل')->suffix(' حزب'),
            ]),

            Infolists\Components\Section::make('الإيواء ومكان الصلاة')->columns(4)->schema([
                Infolists\Components\TextEntry::make('hebergement.chambre.numero')->label('رقم الغرفة')->placeholder('غير مقيم'),
                Infolists\Components\TextEntry::make('hebergement.numero_lit')->label('رقم السرير')->placeholder('—'),
                Infolists\Components\TextEntry::make('placePriere.lieuPriere.nom_ar')->label('مكان الصلاة')->placeholder('—'),
                Infolists\Components\TextEntry::make('placePriere.numero_place')->label('رقم المكان')
                    ->formatStateUsing(fn ($state, $record) => $record->placePriere
                        ? "الصف {$record->placePriere->rangee} — المكان {$state}" : '—'),
            ]),

            Infolists\Components\Section::make('الوثائق المطلوبة')->columns(4)->schema([
                Infolists\Components\TextEntry::make('extrait_naissance')->label('مستخرج الولادة')
                    ->state(fn ($record) => $record->extrait_naissance ? 'متوفر ✓' : 'غير مقدم')
                    ->badge()
                    ->color(fn ($record) => $record->extrait_naissance ? 'success' : 'danger')
                    ->url(fn ($record) => $record->extrait_naissance ? \Illuminate\Support\Facades\Storage::url($record->extrait_naissance) : null, true)
                    ->icon('heroicon-o-document-text'),
                Infolists\Components\TextEntry::make('attestation_scolaire')->label('شهادة مدرسية')
                    ->state(fn ($record) => $record->attestation_scolaire ? 'متوفر ✓' : 'غير مقدم')
                    ->badge()
                    ->color(fn ($record) => $record->attestation_scolaire ? 'success' : 'danger')
                    ->url(fn ($record) => $record->attestation_scolaire ? \Illuminate\Support\Facades\Storage::url($record->attestation_scolaire) : null, true)
                    ->icon('heroicon-o-document-check'),
                Infolists\Components\TextEntry::make('photo')->label('الصورة')
                    ->state(fn ($record) => $record->photo ? 'متوفرة ✓' : 'غير مقدم')
                    ->badge()
                    ->color(fn ($record) => $record->photo ? 'success' : 'danger')
                    ->url(fn ($record) => $record->photo ? \Illuminate\Support\Facades\Storage::url($record->photo) : null, true)
                    ->icon('heroicon-o-photo'),
                Infolists\Components\TextEntry::make('autre_document')->label('وثيقة أخرى')
                    ->state(fn ($record) => $record->autre_document ? 'متوفر ✓' : '—')
                    ->badge()
                    ->color(fn ($record) => $record->autre_document ? 'success' : 'gray')
                    ->url(fn ($record) => $record->autre_document ? \Illuminate\Support\Facades\Storage::url($record->autre_document) : null, true)
                    ->icon('heroicon-o-paper-clip'),
            ]),

            Infolists\Components\Section::make('آخر تقييم للمشرف')->columns(4)
                ->visible(fn ($record) => (bool) $record->derniereEvaluation())
                ->schema([
                    Infolists\Components\TextEntry::make('derniereEvaluation.decision')->label('القرار')
                        ->state(fn ($record) => match ($record->derniereEvaluation()->decision) {
                            'valide'  => 'مقبول',
                            'refuse'  => 'مرفوض',
                            'ajourne' => 'مؤجل',
                            default   => $record->derniereEvaluation()->decision,
                        })
                        ->badge()
                        ->color(fn ($record) => match ($record->derniereEvaluation()->decision) {
                            'valide'  => 'success',
                            'refuse'  => 'danger',
                            default   => 'warning',
                        }),
                    Infolists\Components\TextEntry::make('derniereEvaluation.date_test')->label('تاريخ الاختبار')->date('d/m/Y'),
                    Infolists\Components\TextEntry::make('derniereEvaluation.moyenne')->label('المعدل / 20')
                        ->state(fn ($record) => number_format($record->derniereEvaluation()->moyenne, 2) . ' / 20'),
                    Infolists\Components\TextEntry::make('derniereEvaluation.niveau_propose')->label('المستوى المقترح')->placeholder('—'),
                    Infolists\Components\TextEntry::make('motif_refus')->label('سبب الرفض')
                        ->visible(fn ($record) => (bool) $record->motif_refus)
                        ->color('danger')->icon('heroicon-o-x-circle'),
                    Infolists\Components\TextEntry::make('derniereEvaluation.observations')->label('ملاحظات المشرف')
                        ->placeholder('—')
                        ->columnSpanFull()
                        ->prose(),
                ]),

            Infolists\Components\Section::make('السلوك')->columns(3)->schema([
                Infolists\Components\TextEntry::make('points_conduite')->label('رصيد نقط السلوك')
                    ->state(fn ($record) => $record->comportements()->sum('points'))
                    ->badge()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),
                Infolists\Components\TextEntry::make('nb_incidents')->label('عدد المخالفات')
                    ->state(fn ($record) => $record->comportements()->where('type', 'negatif')->count()),
                Infolists\Components\TextEntry::make('nb_absences')->label('عدد الغيابات')
                    ->state(fn ($record) => $record->rapportsJournaliers()->where('presence', 'absent')->count()),
            ]),
        ]);
    }
}
