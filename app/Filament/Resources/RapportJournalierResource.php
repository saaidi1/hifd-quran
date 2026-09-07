<?php

namespace App\Filament\Resources;

use App\Enums\StatutPresence;
use App\Enums\TypeSeance;
use App\Filament\Resources\RapportJournalierResource\Pages;
use App\Filament\Support\ChampsPlageCoranique;
use App\Models\Etudiant;
use App\Models\RapportJournalier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * التقرير اليومي — rapport quotidien de récitation (تسميع).
 *
 * Périmètre par rôle :
 * - Professeur : ne voit/édite que SES rapports (professeur_id = lui-même, via getEloquentQuery)
 *   et crée ses rapports sous son nom ;
 * - Autres rôles : consultation ; le champ « الأستاذ المشرف » (professeurs + superviseurs actifs)
 *   leur permet de désigner l'encadrant de la séance ;
 * - Chaque séance distingue الحفظ الجديد (hifd_jadid) et الحفظ القديم (hifd_qadim).
 */
class RapportJournalierResource extends Resource
{
    protected static ?string $model = RapportJournalier::class;

    protected static ?string $navigationIcon   = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup  = 'الحفظ والمتابعة';
    protected static ?string $navigationLabel  = 'التقارير اليومية';
    protected static ?string $modelLabel       = 'تقرير يومي';
    protected static ?string $pluralModelLabel = 'التقارير اليومية';
    protected static ?int    $navigationSort   = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            /* En-tête : élève, groupe (déduit automatiquement), encadrant, date et présence. */
            Forms\Components\Section::make('معطيات الحصة')->columns(4)->schema([
                /* Choix de l'élève restreint aux groupes du professeur ; le groupe est recopié à la volée. */
                Forms\Components\Select::make('etudiant_id')->label('الطالب')->required()->searchable()->preload()
                    ->options(fn () => Etudiant::valides()
                        ->when(auth()->user()->estProfesseur(),
                            fn ($q) => $q->whereIn('groupe_id', auth()->user()->groupes()->select('id')))
                        ->get()->pluck('nom_complet_ar', 'id'))
                    ->live()
                    ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('groupe_id', Etudiant::find($state)?->groupe_id)),

                Forms\Components\Select::make('groupe_id')->label('المجموعة')
                    ->relationship('groupe', 'nom_ar')->required()->disabled()->dehydrated(),

                /* « الأستاذ المشرف » : professeurs + superviseurs actifs ; masqué au professeur (c'est lui-même). */
                Forms\Components\Select::make('professeur_id')->label('الأستاذ المشرف')
                    ->options(fn () => \App\Models\User::encadrants()->where('actif', true)
                        ->orderBy('nom_ar')
                        ->get()->mapWithKeys(fn ($p) => [$p->id => $p->nom_ar ?: $p->nom]))
                    ->searchable()->preload()
                    ->rule('exists:users,id')
                    ->required()
                    ->visible(fn () => ! auth()->user()->estProfesseur())
                    ->helperText('اختر الأستاذ الذي أشرف على الحصة'),

                Forms\Components\DatePicker::make('date')->label('التاريخ')->default(now())->required()->maxDate(now()),

                Forms\Components\Select::make('presence')->label('الحضور')
                    ->options(StatutPresence::class)->default(StatutPresence::PRESENT->value)->required()->live(),

                /* Heure d'arrivée demandée uniquement en cas de retard. */
                Forms\Components\TimePicker::make('heure_arrivee')->label('ساعة الوصول')->seconds(false)
                    ->visible(fn (Forms\Get $get) => $get('presence') === StatutPresence::RETARD->value),
            ]),

            /* ----- Le cœur : ce qui a été récité ----- */
            /* Sections masquées si l'élève est absent ; chaque ligne = un passage (nouveau ou ancien hifd). */
            Forms\Components\Section::make('ما تم تسميعه')
                ->description('يمكن إضافة عدة مقاطع : الحفظ الجديد والحفظ القديم.')
                ->visible(fn (Forms\Get $get) => in_array($get('presence'), [StatutPresence::PRESENT->value, StatutPresence::RETARD->value], true))
                ->schema([
                    /* Une ligne par passage récité ; type = hifd_jadid (nouveau) ou hifd_qadim (ancien). */
                    Forms\Components\Repeater::make('lignes')
                        ->relationship()
                        ->label('')
                        ->addActionLabel('إضافة مقطع')
                        ->defaultItems(1)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => TypeSeance::tryFrom($state['type'] ?? '')?->getLabel())
                        ->schema([
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Select::make('type')->label('نوع التسميع')
                                    ->options(TypeSeance::class)->default(TypeSeance::HIFD_JADID->value)->required()->live(),

                                /* Lien optionnel vers le devoir (tache) assigné précédemment à cet élève. */
                            Forms\Components\Select::make('tache_id')->label('المقرر المرتبط')
                                    ->options(fn (Forms\Get $get) => \App\Models\TacheMemorisation::query()
                                        ->where('etudiant_id', $get('../../etudiant_id'))
                                        ->where('statut', 'assignee')
                                        ->with(['sourateDebut', 'sourateFin'])
                                        ->get()->mapWithKeys(fn ($t) => [$t->id => $t->libellePlage()]))
                                    ->helperText('اختياري : يربط التسميع بالواجب المقرر سابقا'),
                            ]),

                            ...ChampsPlageCoranique::schema(),

                            Forms\Components\Grid::make(4)->schema([
                                Forms\Components\TextInput::make('nb_pages')->label('عدد الأوجه')->numeric()->step(0.25)->minValue(0),
                                Forms\Components\TextInput::make('note')->label('النقطة / 20')->numeric()->minValue(0)->maxValue(20)->required(),
                                Forms\Components\TextInput::make('nb_erreurs')->label('عدد الأخطاء')->numeric()->minValue(0)->default(0),
                                Forms\Components\TextInput::make('nb_hesitations')->label('عدد التنبيهات')->numeric()->minValue(0)->default(0),
                            ]),

                            Forms\Components\Textarea::make('observation')->label('ملاحظة الأستاذ')->rows(2),
                        ]),
                ]),

            /* Évaluation transversale du comportement pour la séance. */
            Forms\Components\Section::make('التقويم العام')->columns(2)->schema([
                Forms\Components\TextInput::make('note_comportement')->label('نقطة السلوك / 20')->numeric()->minValue(0)->maxValue(20),
                Forms\Components\Textarea::make('remarques')->label('ملاحظات عامة')->rows(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('date')->label('التاريخ')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('etudiant.nom_complet_ar')->label('الطالب')->searchable(['nom_ar', 'prenom_ar'])->weight('bold'),
                Tables\Columns\TextColumn::make('groupe.nom_ar')->label('المجموعة')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('presence')->label('الحضور')->badge(),
                /* Passages hifd_jadid (الحفظ الجديد) récités, reconstitués depuis les lignes. */
                Tables\Columns\TextColumn::make('hifd')->label('الحفظ الجديد')->wrap()
                    ->state(fn (RapportJournalier $r) => $r->lignes
                        ->where('type', TypeSeance::HIFD_JADID)
                        ->map(fn ($l) => $l->libellePlage())->implode(' • ') ?: '—'),
                /* Passages hifd_qadim (الحفظ القديم / révision). */
                Tables\Columns\TextColumn::make('murajaa')->label('الحفظ القديم')->wrap()
                    ->state(fn (RapportJournalier $r) => $r->lignes
                        ->where('type', TypeSeance::HIFD_QADIM)
                        ->map(fn ($l) => $l->libellePlage())->implode(' • ') ?: '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('note_globale')->label('المعدل')->numeric(2)->badge()
                    /* Vert ≥ 14, orange ≥ 10, rouge en dessous. */
                    ->color(fn ($state) => $state >= 14 ? 'success' : ($state >= 10 ? 'warning' : 'danger'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('professeur.nom_ar')->label('الأستاذ')->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('groupe_id')->label('المجموعة')->relationship('groupe', 'nom_ar'),
                Tables\Filters\SelectFilter::make('professeur_id')->label('الأستاذ')
                    ->relationship('professeur', 'nom_ar')
                    ->visible(fn () => ! auth()->user()->estProfesseur()),
                Tables\Filters\SelectFilter::make('presence')->label('الحضور')->options(StatutPresence::class),
                /* Filtre personnalisé par intervalle de dates. */
                Tables\Filters\Filter::make('periode')->label('الفترة')
                    ->form([
                        Forms\Components\DatePicker::make('du')->label('من'),
                        Forms\Components\DatePicker::make('au')->label('إلى'),
                    ])
                    ->query(fn (Builder $q, array $data) => $q
                        ->when($data['du'], fn ($q, $d) => $q->whereDate('date', '>=', $d))
                        ->when($data['au'], fn ($q, $d) => $q->whereDate('date', '<=', $d))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('عرض'),
                Tables\Actions\EditAction::make()->label('تعديل'),
            ])
            ->defaultSort('date', 'desc');
    }

    /**
     * Le professeur ne voit et ne modifie que ses propres rapports (professeur_id = lui) ;
     * directeur et superviseur accèdent à l'ensemble. Eager-loading des lignes pour la table.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['lignes.sourateDebut', 'lignes.sourateFin', 'etudiant', 'groupe']);

        if (auth()->user()?->estProfesseur()) {
            $query->where('professeur_id', auth()->id());
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRapports::route('/'),
            'create' => Pages\CreateRapport::route('/create'),
            'edit'   => Pages\EditRapport::route('/{record}/edit'),
        ];
    }
}
