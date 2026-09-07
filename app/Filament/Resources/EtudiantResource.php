<?php

namespace App\Filament\Resources;

use App\Enums\StatutInscription;
use App\Filament\Resources\EtudiantResource\Pages;
use App\Filament\Resources\EtudiantResource\RelationManagers;
use App\Models\Chambre;
use App\Models\Etudiant;
use App\Models\Groupe;
use App\Models\LieuPriere;
use App\Models\PlacePriere;
use App\Services\InscriptionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

/**
 * الطلبة — dossier étudiant complet (préinscription → test → affectation).
 *
 * Périmètre par rôle :
 * - Professeur : ne voit que les élèves de SES groupes (voir getEloquentQuery) ;
 * - Superviseur : réalise le test de admission (« اختبار وقبول ») ;
 * - Garde / directeur : affectation aux groupes, logistique (lit, place de prière) ;
 * - Le matricule (رقم التسجيل) sert aussi de numéro de lit et de place de prière.
 */
class EtudiantResource extends Resource
{
    protected static ?string $model = Etudiant::class;

    protected static ?string $navigationIcon  = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'شؤون الطلبة';
    protected static ?string $navigationLabel = 'الطلبة';
    protected static ?string $modelLabel      = 'طالب';
    protected static ?string $pluralModelLabel = 'الطلبة';
    protected static ?int    $navigationSort  = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            /* Inscription en 4 étapes : identité → documents → tuteur → situation. */
            Forms\Components\Wizard::make()->columnSpanFull()->schema([

                /* Étape 1 : état civil de l'élève. */
                Forms\Components\Wizard\Step::make('المعلومات الشخصية')
                    ->description('معلومات الطالب الأساسية')
                    ->icon('heroicon-o-identification')
                    ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('matricule')->label('رقم التسجيل')->disabled()->dehydrated(false),
                        Forms\Components\TextInput::make('nom_ar')->label('النسب')->required()->maxLength(100),
                        Forms\Components\TextInput::make('prenom_ar')->label('الاسم')->required()->maxLength(100),
                        Forms\Components\TextInput::make('nom')->label('النسب (لاتيني)')->maxLength(100),
                        Forms\Components\TextInput::make('prenom')->label('الاسم (لاتيني)')->maxLength(100),
                        Forms\Components\Select::make('sexe')->label('الجنس')
                            ->options(['M' => 'ذكر', 'F' => 'أنثى'])->default('M')->required(),
                        Forms\Components\DatePicker::make('date_naissance')->label('تاريخ الازدياد')->required()->maxDate(now()),
                        Forms\Components\TextInput::make('lieu_naissance')->label('مكان الازدياد'),
                        Forms\Components\TextInput::make('cin')->label('رقم البطاقة الوطنية'),
                        Forms\Components\TextInput::make('telephone')->label('الهاتف')->tel(),
                        Forms\Components\TextInput::make('ville')->label('المدينة'),
                        Forms\Components\TextInput::make('niveau_scolaire')->label('المستوى الدراسي'),
                        Forms\Components\Textarea::make('adresse')->label('العنوان')->columnSpanFull()->rows(2),
                        Forms\Components\FileUpload::make('photo')->label('الصورة')->required()->image()->directory('etudiants')->avatar(),
                    ]),
                ]),

                /* Étape 2 : pièces justificatives obligatoires (PDF ou images). */
                Forms\Components\Wizard\Step::make('الوثائق المطلوبة')
                    ->description('مستخرج الولادة، الشهادة المدرسية والصورة')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\FileUpload::make('extrait_naissance')->label('مستخرج الولادة')->required()
                            ->directory('documents/naissances')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->openable()->downloadable()->imageEditor()->columnSpan(1),
                        Forms\Components\FileUpload::make('attestation_scolaire')->label('شهادة مدرسية')->required()
                            ->directory('documents/attestations')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->openable()->downloadable()->imageEditor()->columnSpan(1),
                        Forms\Components\FileUpload::make('autre_document')->label('وثيقة أخرى (اختياري)')
                            ->directory('documents/autres')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->openable()->downloadable()->columnSpan(1),
                    ]),
                    Forms\Components\Placeholder::make('docs_hint')->label('')
                        ->content('مطلوب عند التسجيل المبدئي : مستخرج الولادة، الصورة، الشهادة المدرسية'),
                ]),

                /* Étape 3 : contact du tuteur légal. */
                Forms\Components\Wizard\Step::make('ولي الأمر')
                    ->description('معلومات ولي أمر الطالب')
                    ->icon('heroicon-o-user')
                    ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('tuteur_nom')->label('اسم ولي الأمر')->required(),
                        Forms\Components\Select::make('tuteur_lien')->label('صلة القرابة')
                            ->options(['père' => 'الأب', 'mère' => 'الأم', 'frère' => 'الأخ', 'oncle' => 'العم', 'autre' => 'أخرى']),
                        Forms\Components\TextInput::make('tuteur_telephone')->label('هاتف ولي الأمر')->tel()->required(),
                    ]),
                ]),

                /* Étape 4 : mémorisation à l'entrée et situation (statut/groupe pilotés par le workflow). */
                Forms\Components\Wizard\Step::make('الوضعية')
                    ->description('المحفوظ عند التسجيل والداخلية')
                    ->icon('heroicon-o-academic-cap')
                    ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('hifd_initial_hizb')->label('المحفوظ عند التسجيل (حزب)')
                            ->numeric()->minValue(0)->maxValue(60)->default(0),
                        Forms\Components\Select::make('statut')->label('حالة التسجيل')
                            ->options(StatutInscription::class)
                            ->disabled()   // pilotée par le workflow, jamais à la main
                            ->dehydrated(false),
                        Forms\Components\Select::make('groupe_id')->label('المجموعة')
                            ->relationship('groupe', 'nom_ar')
                            ->disabled()   // passe par l'action « إسناد إلى مجموعة »
                            ->dehydrated(false),
                        Forms\Components\Toggle::make('interne')->label('مقيم بالداخلية')->inline(false),
                        Forms\Components\Toggle::make('actif')->label('نشيط')->default(true)->inline(false),
                    ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')->label('')->circular()->defaultImageUrl(asset('images/avatar.png')),
                /* Matricule = numéro d'inscription ; sert aussi de numéro de lit et de place de prière. */
                Tables\Columns\TextColumn::make('matricule')->label('رقم التسجيل')->searchable()->sortable()->copyable(),
                Tables\Columns\TextColumn::make('nom_complet_ar')->label('الاسم الكامل')
                    ->searchable(['nom_ar', 'prenom_ar'])->weight('bold'),
                Tables\Columns\TextColumn::make('statut')->label('الحالة')->badge(),
                /* Dernier avis : observations du test ou motif de refus. */
                Tables\Columns\TextColumn::make('decision')->label('ملاحظة القرار')->toggleable()
                    ->state(fn (Etudiant $record) => $record->derniereEvaluation()?->observations
                        ?? $record->motif_refus ?? '—')
                    ->limit(40)
                    ->placeholder('—')
                    ->tooltip(fn (Etudiant $record) => $record->derniereEvaluation()?->observations
                        ? 'ملاحظات: ' . $record->derniereEvaluation()->observations
                        : ($record->motif_refus ? 'سبب الرفض: ' . $record->motif_refus : 'لا توجد ملاحظة')),
                /* Compteur de pièces (x/4) avec détail au survol. */
                Tables\Columns\TextColumn::make('documents')->label('الوثائق')
                    ->state(fn (Etudiant $record) => count($record->documents()))
                    ->formatStateUsing(fn (Etudiant $record) => count($record->documents()) . '/4')
                    ->badge()
                    ->color(fn (Etudiant $record) => $record->documentsComplets() ? 'success' : 'warning')
                    ->tooltip(function (Etudiant $record) {
                        $docs = [
                            $record->extrait_naissance ? 'مستخرج الولادة ✓' : 'مستخرج الولادة —',
                            $record->attestation_scolaire ? 'شهادة مدرسية ✓' : 'شهادة مدرسية —',
                            $record->photo ? 'الصورة ✓' : 'الصورة —',
                            $record->autre_document ? 'وثيقة أخرى ✓' : 'وثيقة أخرى —',
                        ];

                        return implode(' • ', $docs);
                    }),
                Tables\Columns\TextColumn::make('groupe.nom_ar')->label('المجموعة')->badge()->color('gray')
                    ->placeholder('غير مسند'),
                Tables\Columns\TextColumn::make('groupe.professeur.nom_ar')->label('الأستاذ')->toggleable(),
                Tables\Columns\TextColumn::make('hifd_initial_hizb')->label('المحفوظ (حزب)')->numeric(2)->sortable(),
                Tables\Columns\IconColumn::make('interne')->label('الداخلية')->boolean()->toggleable(),
                /* Lit = numéro d'inscription de l'élève (attribution automatique). */
                Tables\Columns\TextColumn::make('hebergement.numero_lit')->label('رقم السرير')
                    ->prefix('سرير ')->toggleable()->placeholder('—'),
                Tables\Columns\TextColumn::make('date_preinscription')->label('تاريخ التسجيل')->date('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')->label('الحالة')->options(StatutInscription::class),
                Tables\Filters\SelectFilter::make('groupe_id')->label('المجموعة')->relationship('groupe', 'nom_ar'),
                Tables\Filters\TernaryFilter::make('interne')->label('مقيم بالداخلية'),
                /* Élèves admis (VALIDE) mais pas encore rattachés à une halaqa. */
                Tables\Filters\Filter::make('sans_groupe')->label('بدون مجموعة')
                    ->query(fn (Builder $q) => $q->whereNull('groupe_id')->where('statut', StatutInscription::VALIDE)),
            ])
            ->actions([
                /* ---- المشرف على الأساتذة : اختبار وقبول ---- */
                /* Test d'admission (notes hifd/tajwid/lecture) ; la décision passe par InscriptionService. */
                Tables\Actions\Action::make('evaluer')
                    ->label('اختبار وقبول')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->color('warning')
                    ->visible(fn (Etudiant $record) => auth()->user()->estSuperviseur()
                        && ! in_array($record->statut, [StatutInscription::VALIDE, StatutInscription::REFUSE], true))
                    ->form([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\DatePicker::make('date_test')->label('تاريخ الاختبار')->default(now())->required(),
                            Forms\Components\Select::make('sourate_testee_id')->label('السورة المختبرة')
                                ->relationship('sourateTestee', 'nom_ar')->searchable()->preload(),
                            Forms\Components\TextInput::make('hizb_maitrise')->label('المحفوظ (حزب)')
                                ->numeric()->minValue(0)->maxValue(60)->required(),
                            Forms\Components\TextInput::make('note_hifd')->label('نقطة الحفظ / 20')->numeric()->minValue(0)->maxValue(20)->required(),
                            Forms\Components\TextInput::make('note_tajwid')->label('نقطة التجويد / 20')->numeric()->minValue(0)->maxValue(20)->required(),
                            Forms\Components\TextInput::make('note_lecture')->label('نقطة التلاوة / 20')->numeric()->minValue(0)->maxValue(20)->required(),
                        ]),
                        Forms\Components\Radio::make('decision')->label('القرار')->required()->inline()
                            ->options(['valide' => 'قبول', 'refuse' => 'رفض', 'ajourne' => 'تأجيل'])->live(),
                        Forms\Components\TextInput::make('niveau_propose')->label('المستوى المقترح')
                            ->visible(fn (Forms\Get $get) => $get('decision') === 'valide'),
                        Forms\Components\Textarea::make('motif')->label('سبب الرفض')->rows(2)
                            ->required(fn (Forms\Get $get) => $get('decision') === 'refuse')
                            ->visible(fn (Forms\Get $get) => in_array($get('decision'), ['refuse', 'ajourne'], true)),
                        Forms\Components\Textarea::make('observations')->label('ملاحظات')->rows(3),
                    ])
                    ->action(function (Etudiant $record, array $data) {
                        app(InscriptionService::class)->evaluer($record, $data, auth()->user());

                        Notification::make()->success()
                            ->title('تم تسجيل القرار')
                            ->body($data['decision'] === 'valide'
                                ? 'يمكن للحارس العام الآن إسناد الطالب إلى مجموعة.'
                                : 'لن يتم إسناد الطالب إلى أي مجموعة.')
                            ->send();
                    }),

                /* ---- الحارس العام : إسناد إلى مجموعة ---- */
                /* Affectation à un groupe actif ; la capacité est vérifiée par le service (ValidationException interceptée). */
                Tables\Actions\Action::make('affecter')
                    ->label('إسناد إلى مجموعة')
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->visible(fn (Etudiant $record) => auth()->user()->can('affecter', $record))
                    ->form([
                        Forms\Components\Select::make('groupe_id')->label('المجموعة')->required()
                            ->options(fn () => Groupe::where('actif', true)->get()
                                ->mapWithKeys(fn (Groupe $g) => [
                                    $g->id => $g->nom_ar . ' — ' . $g->placesRestantes() . ' مكان شاغر',
                                ]))
                            ->searchable(),
                        Forms\Components\TextInput::make('motif')->label('السبب'),
                    ])
                    ->action(function (Etudiant $record, array $data) {
                        try {
                            app(InscriptionService::class)->affecter(
                                $record, Groupe::findOrFail($data['groupe_id']), auth()->user(), $data['motif'] ?? null
                            );
                            Notification::make()->success()->title('تم الإسناد إلى المجموعة')->send();
                        } catch (ValidationException $e) {
                            Notification::make()->danger()->title('تعذر الإسناد')
                                ->body(collect($e->errors())->flatten()->first())->send();
                        }
                    }),

                /* ---- الحارس العام : الإيواء ومكان الصلاة ---- */
                /* Logistique interne : le lit et la place de prière portent automatiquement le numéro d'inscription. */
                Tables\Actions\ActionGroup::make([
                    /* Hébergement : n'autorise que les chambres où le lit au numéro du matricule est libre. */
                    Tables\Actions\Action::make('hebergement')
                        ->label('الإيواء ورقم السرير')
                        ->icon('heroicon-o-home-modern')
                        ->visible(fn (Etudiant $record) => auth()->user()->can('gererLogistique', $record))
                        ->modalDescription(fn (Etudiant $record) => 'يُخصص السرير تلقائياً بالرقم المطابق لرقم التسجيل : ' . $record->numeroInscription())
                        ->form([
                            Forms\Components\Select::make('chambre_id')->label('الغرفة')->required()->live()
                                ->options(function (Etudiant $record) {
                                    $numero = $record->numeroInscription();

                                    return Chambre::where('actif', true)->get()
                                        ->filter(fn (Chambre $c) => in_array($numero, $c->litsLibres(), true))
                                        ->mapWithKeys(fn (Chambre $c) => [$c->id => "غرفة {$c->numero} — السرير {$numero} شاغر"]);
                                })
                                ->helperText(fn (Etudiant $record) => 'رقم التسجيل: ' . $record->numeroInscription()),
                            Forms\Components\TextInput::make('observation')->label('ملاحظة'),
                        ])
                        ->action(function (Etudiant $record, array $data) {
                            $numero  = $record->numeroInscription();
                            $chambre = Chambre::findOrFail($data['chambre_id']);

                            if (! in_array($numero, $chambre->litsLibres(), true)) {
                                Notification::make()->danger()
                                    ->title("السرير رقم {$numero} (المطابق لرقم التسجيل) غير متاح في هذه الغرفة.")
                                    ->send();

                                return;
                            }

                            $record->hebergement?->update(['actif' => false, 'date_fin' => now()]);
                            $record->hebergement()->create([
                                'chambre_id'   => $data['chambre_id'],
                                'numero_lit'   => $numero,
                                'date_debut'   => now(),
                                'attribue_par' => auth()->id(),
                                'observation'  => $data['observation'] ?? null,
                                'actif'        => true,
                            ]);
                            $record->update(['interne' => true]);
                            Notification::make()->success()->title("تم تخصيص السرير رقم {$numero}")->send();
                        }),

                    /* Place de prière : rangée libre, numéro = matricule, dans le lieu choisi. */
                    Tables\Actions\Action::make('place_priere')
                        ->label('مكان الصلاة')
                        ->icon('heroicon-o-map-pin')
                        ->visible(fn (Etudiant $record) => auth()->user()->can('gererLogistique', $record))
                        ->modalDescription(fn (Etudiant $record) => 'يُخصص المكان تلقائياً بالرقم المطابق لرقم التسجيل : ' . $record->numeroInscription())
                        ->form([
                            Forms\Components\Select::make('lieu_priere_id')->label('المكان')->required()
                                ->options(fn () => LieuPriere::where('actif', true)->pluck('nom_ar', 'id')),
                            Forms\Components\TextInput::make('rangee')->label('الصف')->numeric()->minValue(1)->required(),
                        ])
                        ->action(function (Etudiant $record, array $data) {
                            $numero = $record->numeroInscription();

                            $occupe = PlacePriere::where('lieu_priere_id', $data['lieu_priere_id'])
                                ->where('actif', true)
                                ->where('numero_place', $numero)
                                ->when($record->placePriere, fn ($q) => $q->whereKeyNot($record->placePriere->id))
                                ->exists();

                            if ($occupe) {
                                Notification::make()->danger()
                                    ->title("المكان رقم {$numero} (المطابق لرقم التسجيل) محجوز بالفعل في هذا المصلى.")
                                    ->send();

                                return;
                            }

                            $record->placePriere?->update(['actif' => false, 'date_fin' => now()]);
                            $record->placePriere()->create([
                                'lieu_priere_id' => $data['lieu_priere_id'],
                                'rangee'         => $data['rangee'],
                                'numero_place'   => $numero,
                                'date_debut'     => now(),
                                'attribue_par'   => auth()->id(),
                                'actif'          => true,
                            ]);
                            Notification::make()->success()->title("تم تخصيص مكان الصلاة رقم {$numero}")->send();
                        }),

                    Tables\Actions\ViewAction::make()->label('الملف الكامل'),
                    Tables\Actions\EditAction::make()->label('تعديل'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    /* Affectation en masse : compte les succès et signale les refus (capacité / statut). */
                    Tables\Actions\BulkAction::make('affecter_groupe')
                        ->label('إسناد جماعي إلى مجموعة')
                        ->icon('heroicon-o-users')
                        ->visible(fn () => auth()->user()->estGarde() || auth()->user()->estDirecteur())
                        ->form([
                            Forms\Components\Select::make('groupe_id')->label('المجموعة')->required()
                                ->options(fn () => Groupe::where('actif', true)->pluck('nom_ar', 'id')),
                        ])
                        ->action(function ($records, array $data) {
                            $groupe  = Groupe::findOrFail($data['groupe_id']);
                            $service = app(InscriptionService::class);
                            $ok = 0; $ko = 0;

                            foreach ($records as $etudiant) {
                                try { $service->affecter($etudiant, $groupe, auth()->user()); $ok++; }
                                catch (ValidationException) { $ko++; }
                            }

                            Notification::make()->success()
                                ->title("تم إسناد $ok طالب")
                                ->body($ko ? "تعذر إسناد $ko طالب (التسجيل غير مصادق عليه أو المجموعة ممتلئة)." : null)
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\EvaluationsRelationManager::class,
            RelationManagers\RapportsRelationManager::class,
            RelationManagers\ComportementsRelationManager::class,
            RelationManagers\TachesRelationManager::class,
        ];
    }

    /**
     * Le professeur ne voit que les étudiants de ses propres مجموعات ;
     * les autres rôles voient tous les dossiers (avec eager-loading pour la table).
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['groupe.professeur', 'hebergement']);

        if (auth()->user()?->estProfesseur()) {
            $query->whereIn('groupe_id', auth()->user()->groupes()->select('id'));
        }

        return $query;
    }

    /* Badge : nombre de préinscriptions en attente de test. */
    public static function getNavigationBadge(): ?string
    {
        $enAttente = Etudiant::enAttente()->count();

        return $enAttente ? (string) $enAttente : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListEtudiants::route('/'),
            'create' => Pages\CreateEtudiant::route('/create'),
            'view'   => Pages\ViewEtudiant::route('/{record}'),
            'edit'   => Pages\EditEtudiant::route('/{record}/edit'),
        ];
    }
}
